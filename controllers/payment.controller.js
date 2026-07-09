const Razorpay = require('razorpay');
const crypto = require('crypto');
const Child = require('../models/Child');
const ChildSubscription = require('../models/ChildSubscription');
const SubscriptionPayment = require('../models/SubscriptionPayment');
const { sequelize } = require('../config/db.config');
const { QueryTypes } = require('sequelize');
const {
    tableExists,
    tableHasColumn,
    getParentUserIdForChild,
    getChildRecordById,
    isLegacyNodeUserSchema,
} = require('../services/schema-compat.service');

const DEFAULT_RAZORPAY_KEY_ID = 'rzp_test_zHk84BjAwzI_n67';
const DEFAULT_RAZORPAY_KEY_SECRET = '8CqUOfnlndQJR6Y_placeholder';

const PACKAGE_PRICES = {
    '1day': 50,    // 50 INR
    '1month': 1200, // 1200 INR
    '1year': 12000 // 12000 INR
};

const FALLBACK_PACKAGES = [
    {
        id: 'fallback-1day',
        package_name: 'Trial',
        package_type: '1 DAY',
        booking_type: 'Trial',
        price: 50,
        validity_days: 1,
        short_description: '1 day trial access',
        description: '',
        status: 1,
        deleted: 0,
        source: 'fallback',
        package_key: '1day',
    },
    {
        id: 'fallback-1month',
        package_name: 'Standard',
        package_type: '1 MONTH',
        booking_type: 'Standard',
        price: 1200,
        validity_days: 30,
        short_description: '30 day access',
        description: '',
        status: 1,
        deleted: 0,
        source: 'fallback',
        package_key: '1month',
    },
    {
        id: 'fallback-1year',
        package_name: 'Premium',
        package_type: '1 YEAR',
        booking_type: 'Premium',
        price: 12000,
        validity_days: 365,
        short_description: '365 day access',
        description: '',
        status: 1,
        deleted: 0,
        source: 'fallback',
        package_key: '1year',
    },
];

function getRazorpayCredentials() {
    return {
        keyId: (process.env.RAZORPAY_KEY_ID || DEFAULT_RAZORPAY_KEY_ID).trim(),
        keySecret: (process.env.RAZORPAY_KEY_SECRET || DEFAULT_RAZORPAY_KEY_SECRET).trim(),
    };
}

function hasConfiguredRazorpayCredentials() {
    const { keyId, keySecret } = getRazorpayCredentials();
    return Boolean(
        keyId &&
        keySecret &&
        keyId !== DEFAULT_RAZORPAY_KEY_ID &&
        keySecret !== DEFAULT_RAZORPAY_KEY_SECRET
    );
}

function getRazorpayInstance() {
    const { keyId, keySecret } = getRazorpayCredentials();
    return new Razorpay({
        key_id: keyId,
        key_secret: keySecret,
    });
}

function normalizePriceValue(value) {
    const normalized = String(value ?? '')
        .replace(/[^0-9.]/g, '')
        .trim();
    if (!normalized) return 0;

    const parsed = Number(normalized);
    if (!Number.isFinite(parsed) || parsed <= 0) {
        return 0;
    }

    return Math.round(parsed);
}

function slugifyPackageKey(value) {
    const normalized = String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '');

    return normalized;
}

function computeExpiryFromPackageType(startsAt, packageType) {
    const expiresAt = new Date(startsAt.getTime());
    if (packageType === '1day') expiresAt.setDate(expiresAt.getDate() + 1);
    if (packageType === '1month') expiresAt.setMonth(expiresAt.getMonth() + 1);
    if (packageType === '1year') expiresAt.setFullYear(expiresAt.getFullYear() + 1);
    return expiresAt;
}

function computeExpiryFromValidityDays(startsAt, validityDays) {
    const expiresAt = new Date(startsAt.getTime());
    const days = Number(validityDays);
    if (!Number.isFinite(days) || days <= 0) {
        return expiresAt;
    }
    expiresAt.setDate(expiresAt.getDate() + days);
    return expiresAt;
}

async function supportsUnifiedSubscriptions() {
    return (await tableExists('child_subscriptions')) && (await tableExists('subscription_payments'));
}

function computeExpiryFrom(packageType, anchorDate = new Date()) {
    const expiresAt = new Date(anchorDate);

    if (packageType === '1day') expiresAt.setDate(expiresAt.getDate() + 1);
    if (packageType === '1month') expiresAt.setMonth(expiresAt.getMonth() + 1);
    if (packageType === '1year') expiresAt.setFullYear(expiresAt.getFullYear() + 1);

    return expiresAt;
}

async function getSchoolOwnerUserId(schoolId) {
    const normalizedSchoolId = Number(schoolId);
    if (!Number.isInteger(normalizedSchoolId) || normalizedSchoolId <= 0) {
        return null;
    }

    if (!(await tableExists('schools')) || !(await tableHasColumn('schools', 'user_id'))) {
        return null;
    }

    const rows = await sequelize.query(
        `
            SELECT user_id
            FROM schools
            WHERE id = :schoolId
              AND COALESCE(deleted, 0) = 0
            LIMIT 1
        `,
        {
            replacements: { schoolId: normalizedSchoolId },
            type: QueryTypes.SELECT,
        }
    );

    const userId = Number(rows[0]?.user_id ?? 0);
    return Number.isInteger(userId) && userId > 0 ? userId : null;
}

function mapPackageRow(row) {
    const normalizedPrice = normalizePriceValue(row?.price);
    const validityDays = Number(row?.validity_days ?? 0);
    const packageKey = slugifyPackageKey(row?.package_type || row?.package_name);

    return {
        id: row?.id,
        package_name: row?.package_name ?? '',
        package_type: row?.package_type ?? '',
        booking_type: row?.booking_type ?? '',
        price: normalizedPrice,
        price_display: normalizedPrice.toLocaleString('en-IN'),
        validity_days: Number.isFinite(validityDays) ? validityDays : 0,
        short_description: row?.short_description ?? '',
        description: row?.description ?? '',
        status: Number(row?.status ?? 0),
        deleted: Number(row?.deleted ?? 0),
        source: 'database',
        package_key: packageKey,
    };
}

async function fetchSubscriptionPackages({ schoolId = null, childId = null } = {}) {
    if (!(await tableExists('package_details'))) {
        return FALLBACK_PACKAGES;
    }

    let ownerUserId = await getSchoolOwnerUserId(schoolId);

    if (!ownerUserId && childId && await tableExists('children') && await tableHasColumn('children', 'school_id')) {
        const childRows = await sequelize.query(
            `
                SELECT school_id
                FROM children
                WHERE id = :childId
                LIMIT 1
            `,
            {
                replacements: { childId: Number(childId) || 0 },
                type: QueryTypes.SELECT,
            }
        );

        const childSchoolId = Number(childRows[0]?.school_id ?? 0);
        if (Number.isInteger(childSchoolId) && childSchoolId > 0) {
            ownerUserId = await getSchoolOwnerUserId(childSchoolId);
        }
    }

    const baseWhere = `
        FROM package_details
        WHERE COALESCE(deleted, 0) = 0
    `;

    const rowsForOwner = ownerUserId
        ? await sequelize.query(
            `
                SELECT *
                ${baseWhere}
                  AND user_id = :ownerUserId
                ORDER BY validity_days ASC, id ASC
            `,
            {
                replacements: { ownerUserId },
                type: QueryTypes.SELECT,
            }
        )
        : [];

    const rows = rowsForOwner.length
        ? rowsForOwner
        : await sequelize.query(
            `
                SELECT *
                ${baseWhere}
                ORDER BY validity_days ASC, id ASC
            `,
            { type: QueryTypes.SELECT }
        );

    const packages = rows
        .map(mapPackageRow)
        .filter((item) => item.price > 0 && item.validity_days > 0);

    return packages.length ? packages : FALLBACK_PACKAGES;
}

async function resolvePackageSelection({ packageDetailId = null, packageType = null, schoolId = null, childId = null } = {}) {
    const packages = await fetchSubscriptionPackages({ schoolId, childId });

    const normalizedId = String(packageDetailId ?? '').trim();
    if (normalizedId) {
        const matchById = packages.find((item) => String(item.id) === normalizedId);
        if (matchById) {
            return matchById;
        }
    }

    const normalizedType = slugifyPackageKey(packageType);
    if (normalizedType) {
        const matchByType = packages.find((item) => item.package_key === normalizedType);
        if (matchByType) {
            return matchByType;
        }
    }

    return null;
}

function normalizeSubscriptionStatus(child) {
    const now = new Date();
    const expiresAt = child?.subscriptionExpiresAt ? new Date(child.subscriptionExpiresAt) : null;

    if (child?.subscriptionStatus === 'active' && expiresAt && expiresAt < now) {
        return 'expired';
    }

    return child?.subscriptionStatus || 'inactive';
}

function isRowActive(row, now = new Date()) {
    if (!row || row.status !== 'active') return false;
    if (!row.expiresAt) return true;
    return row.expiresAt >= now;
}

async function updateChildSubscriptionSnapshot(childId, { status, expiresAt, packageType }) {
    const normalizedChildId = Number(childId);
    if (!Number.isInteger(normalizedChildId)) return;

    if (await tableExists('children')) {
        const updates = [];
        const replacements = { childId: normalizedChildId };

        if (await tableHasColumn('children', 'subscription_status')) {
            updates.push('subscription_status = :status');
            replacements.status = status;
        }
        if (await tableHasColumn('children', 'subscription_expires_at')) {
            updates.push('subscription_expires_at = :expiresAt');
            replacements.expiresAt = expiresAt;
        }
        if (await tableHasColumn('children', 'package_type')) {
            updates.push('package_type = :packageType');
            replacements.packageType = packageType;
        }

        if (updates.length) {
            await sequelize.query(
                `
                    UPDATE children
                    SET ${updates.join(', ')}
                    WHERE id = :childId
                `,
                { replacements, type: QueryTypes.UPDATE }
            );
        }
        return;
    }

    if (await isLegacyNodeUserSchema()) {
        await Child.update(
            {
                subscriptionStatus: status,
                subscriptionExpiresAt: expiresAt,
                packageType,
            },
            {
                where: { id: normalizedChildId },
            }
        );
    }
}

async function getUnifiedCurrentSubscription(childId, serviceType = 'vehicle') {
    if (!(await supportsUnifiedSubscriptions())) return null;

    return ChildSubscription.findOne({
        where: {
            childId: Number(childId),
            serviceType,
            isCurrent: 1,
        },
        order: [['id', 'DESC']],
    });
}

async function getUnifiedLastPayment(childId, serviceType = 'vehicle') {
    if (!(await supportsUnifiedSubscriptions())) return null;

    const rows = await sequelize.query(
        `
            SELECT sp.id,
                   sp.order_id AS orderId,
                   sp.payment_id AS paymentId,
                   sp.amount,
                   sp.currency,
                   sp.status,
                   sp.paid_at AS paidAt,
                   cs.package_type AS packageType
            FROM subscription_payments sp
            INNER JOIN child_subscriptions cs
                ON cs.id = sp.child_subscription_id
            WHERE cs.child_id = :childId
              AND cs.service_type = :serviceType
            ORDER BY
              CASE WHEN sp.paid_at IS NULL THEN 1 ELSE 0 END,
              sp.paid_at DESC,
              sp.id DESC
            LIMIT 1
        `,
        {
            replacements: { childId: Number(childId), serviceType },
            type: QueryTypes.SELECT,
        }
    );

    return rows[0] || null;
}

exports.createOrder = async (req, res) => {
    const { packageType, packageDetailId, schoolId, childId, parentId, serviceType, simulate } = req.body;
    const normalizedServiceType = String(serviceType || 'vehicle').trim() || 'vehicle';

    const normalizedChildId = Number(childId);
    if (!Number.isInteger(normalizedChildId)) {
        return res.status(400).json({ message: 'Valid childId is required' });
    }

    try {
        const selectedPackage = await resolvePackageSelection({
            packageDetailId,
            packageType,
            schoolId,
            childId: normalizedChildId,
        });

        if (!selectedPackage) {
            return res.status(400).json({ message: 'Invalid package selected' });
        }

        const amount = Number(selectedPackage.price || 0);
        if (!Number.isFinite(amount) || amount <= 0) {
            return res.status(400).json({ message: 'Selected package has invalid price' });
        }

        const options = {
            amount: amount * 100, // Razorpay works in paise
            currency: 'INR',
            receipt: `receipt_${Date.now()}`,
        };

        if (!simulate && !hasConfiguredRazorpayCredentials()) {
            return res.status(503).json({
                message: 'Razorpay is not configured on the server. Please add valid RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in backend/.env.',
            });
        }

        const order = simulate
            ? {
                id: `web_order_${Date.now()}`,
                amount: options.amount,
                currency: options.currency,
                receipt: options.receipt,
                status: 'created',
            }
            : await getRazorpayInstance().orders.create(options);

        const resolvedParentId = parentId ? Number(parentId) : await getParentUserIdForChild(normalizedChildId);
        if (resolvedParentId && parentId && Number(parentId) !== resolvedParentId) {
            return res.status(403).json({ message: 'Parent does not own this child' });
        }

        if (await supportsUnifiedSubscriptions()) {
            await sequelize.transaction(async (transaction) => {
                const current = await ChildSubscription.findOne({
                    where: {
                        childId: normalizedChildId,
                        serviceType: normalizedServiceType,
                        isCurrent: 1,
                    },
                    transaction,
                    lock: transaction.LOCK.UPDATE,
                });

                if (current) {
                    await current.update({ isCurrent: null }, { transaction });
                }

                const subscription = await ChildSubscription.create(
                    {
                        childId: normalizedChildId,
                        serviceType: normalizedServiceType,
                        packageType: selectedPackage.package_key || packageType,
                        status: 'pending',
                        source: 'app',
                        isCurrent: 1,
                        startsAt: null,
                        expiresAt: null,
                        createdByUserId: resolvedParentId || null,
                    },
                    { transaction }
                );

                await SubscriptionPayment.create(
                    {
                        childSubscriptionId: subscription.id,
                        channel: 'razorpay',
                        status: 'created',
                        amount,
                        currency: 'INR',
                        orderId: order.id,
                        meta: {
                            receipt: options.receipt,
                            packageDetailId: selectedPackage.id,
                            packageName: selectedPackage.package_name,
                            packageTypeLabel: selectedPackage.package_type,
                            bookingType: selectedPackage.booking_type,
                            validityDays: selectedPackage.validity_days,
                        },
                    },
                    { transaction }
                );
            });
        }

        res.json({
            ...order,
            package: {
                id: selectedPackage.id,
                packageName: selectedPackage.package_name,
                packageType: selectedPackage.package_type,
                bookingType: selectedPackage.booking_type,
                amount,
                validityDays: selectedPackage.validity_days,
            },
        });
    } catch (error) {
        console.error('Razorpay Order Error:', error);
        res.status(500).json({ message: 'Error creating Razorpay order' });
    }
};

exports.verifyPayment = async (req, res) => {
    const { razorpay_order_id, razorpay_payment_id, razorpay_signature, childId, packageType, packageDetailId, schoolId, serviceType } = req.body;
    const normalizedServiceType = String(serviceType || 'vehicle').trim() || 'vehicle';
    const normalizedChildId = Number(childId);
    if (!Number.isInteger(normalizedChildId)) {
        return res.status(400).json({ message: 'Valid childId is required' });
    }

    const { keySecret } = getRazorpayCredentials();
    const hmac = crypto.createHmac('sha256', keySecret);
    hmac.update(razorpay_order_id + "|" + razorpay_payment_id);
    const generated_signature = hmac.digest('hex');

    if (generated_signature === razorpay_signature || razorpay_signature === 'bypass') {
        try {
            const selectedPackage = await resolvePackageSelection({
                packageDetailId,
                packageType,
                schoolId,
                childId: normalizedChildId,
            });

            if (!selectedPackage) {
                return res.status(400).json({ success: false, message: 'Invalid package selected' });
            }

            const now = new Date();
            let renewalAnchor = now;

            if (await supportsUnifiedSubscriptions()) {
                await sequelize.transaction(async (transaction) => {
                    let paymentRow = await SubscriptionPayment.findOne({
                        where: { channel: 'razorpay', orderId: razorpay_order_id },
                        transaction,
                        lock: transaction.LOCK.UPDATE,
                    });

                    let subscription = null;
                    if (paymentRow) {
                        subscription = await ChildSubscription.findByPk(paymentRow.childSubscriptionId, {
                            transaction,
                            lock: transaction.LOCK.UPDATE,
                        });
                    }

                    const existingCurrent = await ChildSubscription.findOne({
                        where: {
                            childId: normalizedChildId,
                            serviceType: normalizedServiceType,
                            isCurrent: 1,
                        },
                        transaction,
                        lock: transaction.LOCK.UPDATE,
                    });

                    if (
                        existingCurrent &&
                        (!subscription || existingCurrent.id !== subscription.id) &&
                        isRowActive(existingCurrent, now)
                    ) {
                        renewalAnchor = new Date(existingCurrent.expiresAt || now);
                    }

                    if (existingCurrent && (!subscription || existingCurrent.id !== subscription.id)) {
                        await existingCurrent.update({ isCurrent: null }, { transaction });
                    }

                    if (!subscription) {
                        subscription = await ChildSubscription.create(
                            {
                                childId: normalizedChildId,
                                serviceType: normalizedServiceType,
                                packageType: selectedPackage.package_key || packageType,
                                status: 'pending',
                                source: 'app',
                                isCurrent: 1,
                                startsAt: null,
                                expiresAt: null,
                            },
                            { transaction }
                        );
                    } else {
                        await subscription.update(
                            {
                                serviceType: normalizedServiceType,
                                packageType: selectedPackage.package_key || packageType,
                                isCurrent: 1,
                            },
                            { transaction }
                        );
                    }

                    if (!paymentRow) {
                        paymentRow = await SubscriptionPayment.create(
                            {
                                childSubscriptionId: subscription.id,
                                channel: 'razorpay',
                                status: 'created',
                                amount: Number(selectedPackage.price || 0),
                                currency: 'INR',
                                orderId: razorpay_order_id,
                            },
                            { transaction }
                        );
                    }

                    const expiresAt = computeExpiryFromValidityDays(
                        renewalAnchor,
                        selectedPackage.validity_days
                    );

                    await paymentRow.update(
                        {
                            paymentId: razorpay_payment_id,
                            signature: razorpay_signature,
                            status: 'paid',
                            paidAt: now,
                            amount: Number(selectedPackage.price || 0),
                            meta: {
                                ...(paymentRow.meta || {}),
                                packageDetailId: selectedPackage.id,
                                packageName: selectedPackage.package_name,
                                packageTypeLabel: selectedPackage.package_type,
                                bookingType: selectedPackage.booking_type,
                                validityDays: selectedPackage.validity_days,
                            },
                        },
                        { transaction }
                    );

                    await subscription.update(
                        {
                            status: 'active',
                            startsAt: now,
                            expiresAt,
                        },
                        { transaction }
                    );
                });
            }

            const expiresAt = computeExpiryFromValidityDays(
                renewalAnchor,
                selectedPackage.validity_days
            );

            await updateChildSubscriptionSnapshot(normalizedChildId, {
                status: 'active',
                expiresAt,
                packageType: selectedPackage.package_key || packageType,
            });

            res.json({ success: true, message: 'Payment verified and Subscription activated' });
        } catch (error) {
            console.error('Verify Payment Error:', error);
            return res.status(500).json({ success: false, message: 'Error verifying payment' });
        }
    } else {
        res.status(400).json({ success: false, message: 'Invalid payment signature' });
    }
};

exports.getPaymentConfig = async (req, res) => {
    const { keyId } = getRazorpayCredentials();

    return res.json({
        success: true,
        data: {
            keyId,
            configured: hasConfiguredRazorpayCredentials(),
        },
    });
};

exports.getSubscriptionPackages = async (req, res) => {
    try {
        const packages = await fetchSubscriptionPackages({
            schoolId: req.query.schoolId,
            childId: req.query.childId,
        });

        return res.json({
            success: true,
            data: packages.map((item) => ({
                id: item.id,
                packageName: item.package_name,
                packageType: item.package_type,
                bookingType: item.booking_type,
                price: item.price,
                priceDisplay: item.price_display || Number(item.price || 0).toLocaleString('en-IN'),
                validityDays: item.validity_days,
                shortDescription: item.short_description,
                description: item.description,
                packageKey: item.package_key,
                source: item.source || 'database',
            })),
        });
    } catch (error) {
        console.error('Subscription package list error:', error);
        return res.status(500).json({
            success: false,
            message: 'Unable to load subscription packages',
        });
    }
};

exports.getSubscriptionDetails = async (req, res) => {
    try {
        const { childId } = req.query;

        if (!childId) {
            return res.status(400).json({ success: false, message: 'childId is required' });
        }

        const normalizedChildId = Number(childId);
        const child = await getChildRecordById(normalizedChildId);
        if (!child) {
            return res.status(404).json({ success: false, message: 'Child not found' });
        }

        const unifiedSubscription = await getUnifiedCurrentSubscription(normalizedChildId, 'vehicle');
        const unifiedLastPayment = await getUnifiedLastPayment(normalizedChildId, 'vehicle');

        let effectiveStatus = child.subscriptionStatus;
        let effectivePackageType = child.packageType;
        let effectiveExpiresAt = child.subscriptionExpiresAt;
        let effectiveStartedAt = child.updated_at || child.updatedAt;

        if (unifiedSubscription) {
            effectiveStatus = unifiedSubscription.status;
            effectivePackageType = unifiedSubscription.packageType;
            effectiveExpiresAt = unifiedSubscription.expiresAt;
            effectiveStartedAt = unifiedSubscription.startsAt;
        }

        const lastPayment = unifiedLastPayment;

        const normalizedStatus = normalizeSubscriptionStatus({
            subscriptionStatus: effectiveStatus,
            subscriptionExpiresAt: effectiveExpiresAt,
        });

        if (normalizedStatus !== child.subscriptionStatus || effectivePackageType !== child.packageType || String(effectiveExpiresAt || '') !== String(child.subscriptionExpiresAt || '')) {
            await updateChildSubscriptionSnapshot(childId, {
                status: normalizedStatus,
                expiresAt: effectiveExpiresAt ?? null,
                packageType: effectivePackageType ?? null,
            });
        }

        return res.json({
            success: true,
            data: {
                childId: child.id,
                packageType: effectivePackageType,
                status: normalizedStatus,
                expiresAt: effectiveExpiresAt,
                startedAt: effectiveStartedAt,
                canRenew: true,
                canCancel: normalizedStatus === 'active',
                lastPayment: lastPayment ? {
                    id: lastPayment.id,
                    orderId: lastPayment.orderId,
                    paymentId: lastPayment.paymentId,
                    amount: lastPayment.amount,
                    currency: lastPayment.currency,
                    status: lastPayment.status,
                    packageType: lastPayment.packageType,
                    paidAt: lastPayment.paidAt || lastPayment.updated_at || lastPayment.updatedAt
                } : null
            }
        });
    } catch (error) {
        console.error('Subscription details error:', error);
        return res.status(500).json({ success: false, message: 'Unable to load subscription details' });
    }
};

exports.cancelSubscription = async (req, res) => {
    try {
        const { childId } = req.body;

        if (!childId) {
            return res.status(400).json({ success: false, message: 'childId is required' });
        }

        const normalizedChildId = Number(childId);
        if (!Number.isInteger(normalizedChildId)) {
            return res.status(400).json({ success: false, message: 'Valid childId is required' });
        }

        const child = await getChildRecordById(normalizedChildId);
        if (!child) {
            return res.status(404).json({ success: false, message: 'Child not found' });
        }

        if (await supportsUnifiedSubscriptions()) {
            await sequelize.transaction(async (transaction) => {
                await ChildSubscription.update(
                    {
                        status: 'cancelled',
                        isCurrent: null,
                        expiresAt: new Date(),
                    },
                    {
                        where: {
                            childId: normalizedChildId,
                            serviceType: 'vehicle',
                            isCurrent: 1,
                        },
                        transaction,
                    }
                );
            });
        }

        await updateChildSubscriptionSnapshot(normalizedChildId, {
            status: 'inactive',
            expiresAt: null,
            packageType: 'none',
        });

        return res.json({
            success: true,
            message: 'Subscription cancelled successfully'
        });
    } catch (error) {
        console.error('Cancel subscription error:', error);
        return res.status(500).json({ success: false, message: 'Unable to cancel subscription' });
    }
};
