const Child = require('../models/Child');
const User = require('../models/User');
const {
    findUserByLogin,
    getChildrenForParentUser,
    getChildForParentUser,
    getRouteStopsByRouteId,
    isLegacyNodeUserSchema,
    getParentProfileForUser,
    tableHasColumn,
    tableExists,
} = require('../services/schema-compat.service');
const { sequelize } = require('../config/db.config');
const { QueryTypes } = require('sequelize');
const {
    cleanupExpiredTripPins,
    getActiveTripPinForChild,
    regeneratePinForChild,
} = require('../services/child-trip-pin.service');
// const { Child, User } = require('../models'); // adjust path if needed

function getTodayDateKey() {
    const today = new Date();
    return [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, '0'),
        String(today.getDate()).padStart(2, '0'),
    ].join('-');
}


exports.getChildren = async (req, res) => {
    try {
        await cleanupExpiredTripPins();

        const { email } = req.query;
        if (!email) return res.status(400).json({ message: 'Email required' });

        const user = await findUserByLogin(email);
        if (!user) return res.status(404).json({ message: 'User not found' });

        const children = await getChildrenForParentUser(user.id);
        const enrichedChildren = await Promise.all(
            children.map(async (child) => {
                const activeTripPin = await getActiveTripPinForChild(child.id ?? child._id);
                const resolvedPin = activeTripPin?.pin
                    ? String(activeTripPin.pin).trim()
                    : '';

                return {
                    ...child,
                    secretPin: resolvedPin,
                    secret_pin: resolvedPin,
                    pickupPinActive: Boolean(resolvedPin),
                    pickup_pin_active: Boolean(resolvedPin),
                };
            })
        );

        res.json(enrichedChildren);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Server error' });
    }
};

exports.addChild = async (req, res) => {
    try {
        const {
            email,
            name,
            schoolId,
            schoolName,
            className,
            homeAddress,
            homeLat,
            homeLng,
            schoolAddress,
            schoolLat,
            schoolLng,
            secretPin,
            routeId,
            pickupName,
            stopName,
            todayPickupName,
            todayPickupDate,
        } = req.body;

        const user = await findUserByLogin(email);
        if (!user) return res.status(404).json({ message: 'User not found' });

        const normalizedTodayPickupName = String(todayPickupName || '').trim();
        const normalizedTodayPickupDate = String(todayPickupDate || getTodayDateKey()).trim();

        let child = null;
        if (await isLegacyNodeUserSchema()) {
            child = await Child.create({
                parentId: user.id,
                name,
                ...(schoolId !== undefined ? { schoolId } : {}),
                schoolName,
                className,
                homeAddress,
                homeLat,
                homeLng,
                schoolAddress,
                schoolLat,
                schoolLng,
                secretPin,
                ...(routeId !== undefined ? { routeId } : {}),
                ...(pickupName !== undefined ? { pickupName } : {}),
                ...(stopName !== undefined ? { stopName } : {}),
                ...(normalizedTodayPickupName ? { todayPickupName: normalizedTodayPickupName } : {}),
                ...(normalizedTodayPickupName ? { todayPickupDate: normalizedTodayPickupDate } : {}),
            });
        } else {
            const parentProfile = await getParentProfileForUser(user.id);
            const parentProfileId = parentProfile?.id || null;

            if (!parentProfileId) {
                return res.status(409).json({
                    message: 'Parent profile not found. Please complete parent setup first.',
                });
            }

            const columnMap = [
                ['child_name', name],
                ['parent_id', parentProfileId],
                ['school_id', schoolId],
                ['school_name', schoolName],
                ['class', className],
                ['home_address', homeAddress],
                ['homeLat', homeLat],
                ['homeLng', homeLng],
                ['school_address', schoolAddress],
                ['schoolLat', schoolLat],
                ['schoolLng', schoolLng],
                ['secret_pin', secretPin],
                ['route_id', routeId],
                ['pickup_name', pickupName],
                ['stop_name', stopName],
                ['today_pickup_name', normalizedTodayPickupName || null],
                ['today_pickup_date', normalizedTodayPickupName ? normalizedTodayPickupDate : null],
                ['status', 1],
                ['deleted', 0],
            ];

            if (await tableHasColumn('children', 'user_id')) {
                columnMap.push(['user_id', user.id]);
            }

            const filtered = [];
            for (const [column, value] of columnMap) {
                if (value === undefined) continue;
                if (await tableHasColumn('children', column)) {
                    filtered.push([column, value]);
                }
            }

            const columns = filtered.map(([column]) => column);
            const placeholders = filtered.map(([column]) => `:${column}`);
            const replacements = filtered.reduce((acc, [column, value]) => {
                acc[column] = value;
                return acc;
            }, {});

            if (await tableHasColumn('children', 'created_at')) {
                columns.push('created_at');
                placeholders.push('NOW()');
            }
            if (await tableHasColumn('children', 'updated_at')) {
                columns.push('updated_at');
                placeholders.push('NOW()');
            }

            const [insertId] = await sequelize.query(
                `
                    INSERT INTO children (${columns.join(', ')})
                    VALUES (${placeholders.join(', ')})
                `,
                {
                    replacements,
                    type: QueryTypes.INSERT,
                }
            );

            const childId = Number(insertId);
            const createdChildren = await getChildrenForParentUser(user.id);
            child = createdChildren.find((item) => Number(item.id) === childId) || null;
        }

        res.status(201).json({
            success: true,
            message: 'Child added successfully',
            data: child,
        });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error adding child' });
    }
};

exports.getChildRouteStops = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.query?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.query?.email || req.body?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const child = await getChildForParentUser(childId, user.id);
        if (!child) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const routeId = Number(
            child.routeId ??
            child.raw?.route_id ??
            child.raw?.routeId ??
            0
        );
        if (!routeId) {
            return res.json({
                success: true,
                data: [],
            });
        }

        const routeStops = await getRouteStopsByRouteId(routeId);
        const normalizedStops = routeStops
            .filter((stop) => {
                const type = String(stop.type || 'pickup').toLowerCase();
                return type !== 'start' && type !== 'end';
            })
            .map((stop, index) => {
                const sequenceOrder = Number(
                    stop.sequence_order ?? stop.sequenceOrder ?? stop.sequence ?? index + 1
                );
                const pickupName = String(stop.pickup_name ?? stop.name ?? '').trim();
                const stopName = String(stop.stop_name ?? stop.name ?? '').trim();
                const pickupLooksLikeId = /^\d+$/.test(pickupName);
                const labelBase =
                    (!pickupLooksLikeId && pickupName) ||
                    stopName ||
                    pickupName ||
                    `Stop ${index + 1}`;

                return {
                    id: stop.id ?? index + 1,
                    sequenceOrder,
                    pickupName: pickupName || null,
                    stopName: stopName || null,
                    value: stop.id ?? index + 1,
                    label: `${Number.isFinite(sequenceOrder) ? sequenceOrder : index + 1}. ${labelBase}`,
                };
            })
            .filter((stop) => String(stop.id || '').trim() !== '')
            .sort((left, right) => Number(left.sequenceOrder || 0) - Number(right.sequenceOrder || 0));

        return res.json({
            success: true,
            data: normalizedStops,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error fetching child route stops' });
    }
};

function parseMaybeJson(value, fallback) {
    if (value == null) return fallback;
    if (typeof value !== 'string') return value;
    try {
        return JSON.parse(value);
    } catch (_) {
        return fallback;
    }
}

function firstNonEmpty(...values) {
    for (const value of values) {
        const normalized = String(value ?? '').trim();
        if (normalized) return normalized;
    }
    return '';
}

function formatTripDate(value) {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date.toISOString();
}

function normalizeStopKey(value) {
    return String(value ?? '').trim().toLowerCase();
}

function findChildTripStop(stops, childId, tripType) {
    if (!Array.isArray(stops)) return null;
    const normalizedChildId = String(childId);
    const expectedType = tripType === 'afternoon' ? 'dropoff' : 'pickup';

    return stops.find((stop) => {
        const stopChildId = String(stop?.childId ?? stop?.child_id ?? '');
        const stopType = String(stop?.type ?? '').toLowerCase();
        return stopChildId === normalizedChildId && stopType === expectedType;
    }) || stops.find((stop) => {
        const stopChildId = String(stop?.childId ?? stop?.child_id ?? '');
        return stopChildId === normalizedChildId;
    }) || null;
}

function mapTripTimelineStops(stops, childId, tripType) {
    if (!Array.isArray(stops)) return [];

    return stops
        .filter((stop) => stop && typeof stop === 'object')
        .map((stop, index) => {
            const type = String(stop.type || 'stop').toLowerCase();
            const stopChildId = Number(stop.childId ?? stop.child_id ?? 0);
            const isCurrentChild = Number.isInteger(stopChildId) && stopChildId === Number(childId);

            return {
                label: firstNonEmpty(
                    stop.stopLabel,
                    stop.pickupName,
                    stop.stopName,
                    stop.name,
                    `Stop ${index + 1}`
                ),
                type,
                status: firstNonEmpty(stop.status, 'pending'),
                completedAt: formatTripDate(stop.completedAt ?? stop.completed_at),
                sequenceOrder: Number(stop.sequenceOrder ?? stop.sequence_order ?? index + 1),
                childName: firstNonEmpty(stop.name),
                isCurrentChild,
                role: resolveTimelineStopRole(type, tripType, isCurrentChild),
            };
        })
        .sort((left, right) => Number(left.sequenceOrder || 0) - Number(right.sequenceOrder || 0));
}

function resolveTimelineStopRole(type, tripType, isCurrentChild) {
    if (type === 'pickup') {
        return tripType === 'morning'
            ? (isCurrentChild ? 'Child pickup' : 'Pickup')
            : 'School start';
    }

    if (type === 'dropoff') {
        return tripType === 'afternoon'
            ? (isCurrentChild ? 'Child drop' : 'Drop')
            : 'School end';
    }

    return tripType === 'afternoon' ? 'Route start' : 'Route stop';
}

async function getRouteSummary(routeId) {
    if (!routeId || !(await tableExists('routes'))) {
        return {};
    }

    const canJoinDriver =
        (await tableExists('drivers')) &&
        (await tableHasColumn('routes', 'driver_id')) &&
        (await tableHasColumn('drivers', 'driver_name'));
    const routeNameSelect = (await tableHasColumn('routes', 'name'))
        ? 'r.name AS route_name'
        : 'NULL AS route_name';
    const routeDeletedFilter = (await tableHasColumn('routes', 'deleted'))
        ? 'AND COALESCE(r.deleted, 0) = 0'
        : '';
    const driverDeletedFilter = canJoinDriver && (await tableHasColumn('drivers', 'deleted'))
        ? 'AND COALESCE(d.deleted, 0) = 0'
        : '';
    const driverSelect = canJoinDriver ? ', d.driver_name' : ', NULL AS driver_name';
    const driverJoin = canJoinDriver
        ? `LEFT JOIN drivers d ON d.id = r.driver_id ${driverDeletedFilter}`
        : '';

    const rows = await sequelize.query(
        `
            SELECT
                r.id,
                ${routeNameSelect}
                ${driverSelect}
            FROM routes r
            ${driverJoin}
            WHERE r.id = :routeId
              ${routeDeletedFilter}
            LIMIT 1
        `,
        {
            replacements: { routeId },
            type: QueryTypes.SELECT,
        }
    );

    return rows[0] || {};
}

async function getRouteEndpointLabel(routeId) {
    if (!routeId || !(await tableExists('routes'))) {
        return '';
    }

    const hasRouteJson = await tableHasColumn('routes', 'route_json');
    const hasName = await tableHasColumn('routes', 'name');
    const selectColumns = [];
    if (hasRouteJson) selectColumns.push('route_json');
    if (hasName) selectColumns.push('name');
    if (!selectColumns.length) return '';

    const rows = await sequelize.query(
        `
            SELECT ${selectColumns.join(', ')}
            FROM routes
            WHERE id = :routeId
            LIMIT 1
        `,
        {
            replacements: { routeId },
            type: QueryTypes.SELECT,
        }
    );

    const route = rows[0] || {};
    const routeJson = parseMaybeJson(route.route_json, {});
    const endPoint = routeJson && typeof routeJson === 'object' ? routeJson.end_point : null;

    return firstNonEmpty(
        endPoint?.name,
        endPoint?.stop_name,
        endPoint?.pickup_name,
        endPoint?.address,
        route.name,
        'School'
    );
}

exports.getChildTripHistory = async (req, res) => {
    try {
        const rawChildId = req.params.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.query?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const child = await getChildForParentUser(childId, user.id);
        if (!child) {
            return res.status(404).json({ message: 'Child not found' });
        }

        if (!(await tableExists('trips'))) {
            return res.json({ success: true, data: [] });
        }

        const hasRouteId = await tableHasColumn('trips', 'routeId');
        const hasSnakeRouteId = await tableHasColumn('trips', 'route_id');
        const hasCreatedAt = await tableHasColumn('trips', 'createdAt');
        const hasSnakeCreatedAt = await tableHasColumn('trips', 'created_at');
        const routeId = Number(child.routeId ?? child.raw?.route_id ?? 0);
        const routeSummary = await getRouteSummary(routeId);
        const routeEndpointLabel = await getRouteEndpointLabel(routeId);
        const createdColumn = hasCreatedAt ? 'createdAt' : (hasSnakeCreatedAt ? 'created_at' : 'id');
        const routePredicate = routeId && (hasRouteId || hasSnakeRouteId)
            ? `WHERE ${hasRouteId ? 'routeId' : 'route_id'} = :routeId`
            : '';

        const trips = await sequelize.query(
            `
                SELECT *
                FROM trips
                ${routePredicate}
                ORDER BY ${createdColumn} DESC, id DESC
                LIMIT 50
            `,
            {
                replacements: routePredicate ? { routeId } : {},
                type: QueryTypes.SELECT,
            }
        );

        const childName = child.name || child.child_name || 'Child';
        const pickupLabel = firstNonEmpty(child.todayPickupLabel, child.pickupLabel, child.effectivePickupName, child.pickupName);
        const dropLabel = firstNonEmpty(child.stopName, child.stop_name, child.schoolName, 'School');

        const data = trips
            .map((trip) => {
                const tripType = String(trip.tripType ?? trip.trip_type ?? 'morning').toLowerCase() === 'afternoon'
                    ? 'afternoon'
                    : 'morning';
                const stops = parseMaybeJson(trip.stops, []);
                const childStop = findChildTripStop(stops, childId, tripType);

                if (Array.isArray(stops) && stops.length && !childStop && routeId) {
                    return null;
                }

                const childStopLabel = firstNonEmpty(
                    childStop?.stopLabel,
                    childStop?.pickupName,
                    childStop?.name
                );
                const pickupStop = tripType === 'afternoon'
                    ? firstNonEmpty(routeEndpointLabel, routeSummary.route_name, 'School')
                    : firstNonEmpty(childStopLabel, pickupLabel);
                const dropStop = tripType === 'afternoon'
                    ? firstNonEmpty(childStopLabel, pickupLabel)
                    : firstNonEmpty(routeEndpointLabel, dropLabel, routeSummary.route_name, 'School');

                return {
                    id: trip.id,
                    childId,
                    childName,
                    tripType,
                    status: firstNonEmpty(childStop?.status, trip.status, 'waiting'),
                    routeName: firstNonEmpty(routeSummary.route_name, child.routeName),
                    driverName: firstNonEmpty(routeSummary.driver_name),
                    pickupLabel: pickupStop,
                    dropLabel: dropStop,
                    stops: mapTripTimelineStops(stops, childId, tripType),
                    startedAt: formatTripDate(trip.createdAt ?? trip.created_at),
                    updatedAt: formatTripDate(trip.updated_at ?? trip.updatedAt),
                };
            })
            .filter(Boolean);

        return res.json({ success: true, data });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error fetching child trip history' });
    }
};

exports.updateChild = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const payload = {
            name: req.body?.name,
            schoolId: req.body?.schoolId,
            schoolName: req.body?.schoolName,
            className: req.body?.className,
            homeAddress: req.body?.homeAddress,
            homeLat: req.body?.homeLat,
            homeLng: req.body?.homeLng,
            schoolAddress: req.body?.schoolAddress,
            schoolLat: req.body?.schoolLat,
            schoolLng: req.body?.schoolLng,
            secretPin: req.body?.secretPin,
            routeId: req.body?.routeId,
            pickupName: req.body?.pickupName,
            stopName: req.body?.stopName,
            todayPickupName: req.body?.todayPickupName,
            todayPickupDate: req.body?.todayPickupDate,
        };

        const ignoredFields = [];

        if (await isLegacyNodeUserSchema()) {
            const updates = {};
            for (const [key, value] of Object.entries(payload)) {
                if (value !== undefined) {
                    updates[key] = value;
                }
            }

            if (!Object.keys(updates).length) {
                return res.status(422).json({ message: 'No supported fields provided' });
            }

            await Child.update(updates, {
                where: { id: childId, parentId: user.id },
            });
        } else {
            const setClauses = [];
            const replacements = { childId };
            const columnMap = [
                ['name', 'child_name'],
                ['schoolId', 'school_id'],
                ['schoolName', 'school_name'],
                ['className', 'class'],
                ['homeAddress', 'home_address'],
                ['secretPin', 'secret_pin'],
                ['homeLat', 'homeLat'],
                ['homeLng', 'homeLng'],
                ['schoolAddress', 'school_address'],
                ['schoolLat', 'schoolLat'],
                ['schoolLng', 'schoolLng'],
                ['routeId', 'route_id'],
                ['pickupName', 'pickup_name'],
                ['stopName', 'stop_name'],
                ['todayPickupName', 'today_pickup_name'],
                ['todayPickupDate', 'today_pickup_date'],
            ];

            for (const [field, column] of columnMap) {
                const value = payload[field];
                if (value === undefined) continue;

                if (await tableHasColumn('children', column)) {
                    setClauses.push(`${column} = :${field}`);
                    replacements[field] = value;
                } else {
                    ignoredFields.push(field);
                }
            }

            if (!setClauses.length) {
                return res.status(422).json({
                    message: 'No supported fields could be updated in shared-database mode',
                    ignoredFields,
                });
            }

            await sequelize.query(
                `
                    UPDATE children
                    SET ${setClauses.join(', ')}
                    WHERE id = :childId
                    LIMIT 1
                `,
                {
                    replacements,
                    type: QueryTypes.UPDATE,
                }
            );
        }

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: ignoredFields.length
                ? 'Child updated with some fields skipped due to schema limits'
                : 'Child updated successfully',
            ignoredFields,
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error updating child' });
    }
};

exports.regenerateChildPin = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const currentPin = existingChild.secretPin ?? existingChild.secret_pin ?? '';
        const pin = await regeneratePinForChild(childId, currentPin);
        if (!pin) {
            return res.status(422).json({ message: 'Unable to regenerate PIN for this child' });
        }

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: 'PIN regenerated successfully',
            pin,
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error regenerating child PIN' });
    }
};

exports.setTodayPickupStop = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();
        const pickupName = String(req.body?.pickupName || req.body?.todayPickupName || '').trim();
        const pickupDate = String(req.body?.pickupDate || req.body?.todayPickupDate || getTodayDateKey()).trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        if (!pickupName) {
            return res.status(422).json({ message: 'pickupName is required' });
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(pickupDate)) {
            return res.status(422).json({ message: 'pickupDate must be in YYYY-MM-DD format' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const hasPickupNameColumn = await tableHasColumn('children', 'today_pickup_name');
        const hasPickupDateColumn = await tableHasColumn('children', 'today_pickup_date');
        if (!hasPickupNameColumn || !hasPickupDateColumn) {
            return res.status(409).json({
                message: 'Today pickup override columns are not available yet. Please run the latest migrations.',
            });
        }

        await sequelize.query(
            `
                UPDATE children
                SET today_pickup_name = :pickupName,
                    today_pickup_date = :pickupDate
                WHERE id = :childId
                LIMIT 1
            `,
            {
                replacements: {
                    childId,
                    pickupName,
                    pickupDate,
                },
                type: QueryTypes.UPDATE,
            }
        );

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: 'Today pickup stop saved successfully',
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error saving today pickup stop' });
    }
};

exports.clearTodayPickupStop = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const hasPickupNameColumn = await tableHasColumn('children', 'today_pickup_name');
        const hasPickupDateColumn = await tableHasColumn('children', 'today_pickup_date');
        if (!hasPickupNameColumn || !hasPickupDateColumn) {
            return res.status(409).json({
                message: 'Today pickup override columns are not available yet. Please run the latest migrations.',
            });
        }

        await sequelize.query(
            `
                UPDATE children
                SET today_pickup_name = NULL,
                    today_pickup_date = NULL
                WHERE id = :childId
                LIMIT 1
            `,
            {
                replacements: { childId },
                type: QueryTypes.UPDATE,
            }
        );

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: 'Today pickup stop cleared successfully',
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error clearing today pickup stop' });
    }
};

// exports.deleteChild = async (req, res) => {
//     try {
//         const rawChildId = req.params.id ?? req.body?.id;
//         const childId = parseInt(rawChildId, 10);
//         const email = req.query.email?.trim()?.toLowerCase();

//         if (!rawChildId || !Number.isInteger(childId)) {
//             return res.status(400).json({ message: 'Valid child id is required' });
//         }

//         if (!email) {
//             return res.status(400).json({ message: 'Email required' });
//         }

//         const user = await User.findOne({ where: { email } });
//         if (!user) {
//             return res.status(404).json({ message: 'User not found' });
//         }

//         const child = await Child.findOne({ where: { id: childId, parentId: user.id } });
//         if (!child) {
//             return res.status(404).json({ message: 'Child not found' });
//         }

//         await child.destroy();
//         return res.json({ success: true, message: 'Child deleted successfully' });
//     } catch (err) {
//         console.error(err);
//         return res.status(500).json({ message: 'Error deleting child' });
//     }
// };


// exports.deleteChild = async (req, res) => {
//   try {
//     const childId = parseInt(req.params.id, 10);
//     const parentId = req.user.id; // from auth middleware

//     const child = await Child.findOne({
//       where: { id: childId, parentId }
//     });

//     if (!child) {
//       return res.status(404).json({ message: 'Child not found' });
//     }

//     await child.destroy();

//     return res.json({
//       success: true,
//       message: 'Child deleted successfully',
//     });
//   } catch (err) {
//     return res.status(500).json({ message: 'Error deleting child' });
//   }
// };



exports.deleteChild = async (req, res) => {
  try {
    const childId = parseInt(req.params.id, 10);
    const email = req.query.email;

    if (!childId) {
      return res.status(400).json({ message: 'Valid child id required' });
    }

    if (!email) {
      return res.status(400).json({ message: 'Email required' });
    }

    const user = await findUserByLogin(email);

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (!(await isLegacyNodeUserSchema())) {
      const child = await getChildForParentUser(childId, user.id);

      if (!child) {
        return res.status(404).json({ message: 'Child not found' });
      }

      return res.status(409).json({
        message: 'Child deletion is managed from the Laravel admin or school panel in shared-database mode',
      });
    }

    const child = await Child.findOne({
      where: { id: childId, parentId: user.id }
    });

    if (!child) {
      return res.status(404).json({ message: 'Child not found' });
    }

    await child.destroy();

    return res.json({
      success: true,
      message: 'Child deleted successfully',
    });

  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error deleting child' });
  }
};
