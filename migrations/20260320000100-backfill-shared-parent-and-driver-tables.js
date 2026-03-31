'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => tables.includes(String(name).toLowerCase());
    const describe = async (tableName) => {
      try {
        return await queryInterface.describeTable(tableName);
      } catch (_) {
        return null;
      }
    };

    if (hasTable('parents') && hasTable('parent_profiles')) {
      await queryInterface.sequelize.query(`
        UPDATE parents p
        INNER JOIN parent_profiles pp
          ON pp.user_id = COALESCE(p.login_user_id, p.user_id)
        SET
          p.email = COALESCE(NULLIF(p.email, ''), pp.email),
          p.father_name = COALESCE(NULLIF(p.father_name, ''), pp.full_name),
          p.mother_name = COALESCE(NULLIF(p.mother_name, ''), pp.mother_name),
          p.contact_number = COALESCE(NULLIF(p.contact_number, ''), pp.phone_number),
          p.alternative_contact_number = COALESCE(NULLIF(p.alternative_contact_number, ''), pp.alternate_phone),
          p.address_1 = COALESCE(NULLIF(p.address_1, ''), pp.home_address),
          p.city = COALESCE(NULLIF(p.city, ''), pp.city),
          p.state = COALESCE(NULLIF(p.state, ''), pp.state),
          p.pincode = COALESCE(NULLIF(p.pincode, ''), pp.pincode)
        WHERE COALESCE(p.deleted, 0) = 0
      `);
    }

    if (hasTable('drivers') && hasTable('driverdetails')) {
      const driversDescription = await describe('drivers');
      const driverDetailsDescription = await describe('driverdetails');
      const hasDriverColumn = (columnName) =>
        !!(driversDescription && Object.prototype.hasOwnProperty.call(driversDescription, columnName));
      const hasDriverDetailsColumn = (columnName) =>
        !!(driverDetailsDescription && Object.prototype.hasOwnProperty.call(driverDetailsDescription, columnName));

      const updates = [];
      if (hasDriverColumn('driver_name') && hasDriverDetailsColumn('fullName')) {
        updates.push(`d.driver_name = COALESCE(NULLIF(d.driver_name, ''), dd.fullName)`);
      }
      if (hasDriverColumn('license_no') && hasDriverDetailsColumn('licenseNumber')) {
        updates.push(`d.license_no = COALESCE(NULLIF(d.license_no, ''), dd.licenseNumber)`);
      }
      if (hasDriverColumn('driver_phone') && hasDriverDetailsColumn('phoneNumber')) {
        updates.push(`d.driver_phone = COALESCE(NULLIF(d.driver_phone, ''), dd.phoneNumber)`);
      }
      if (hasDriverColumn('vehicle_id') && hasDriverDetailsColumn('vehicleId')) {
        updates.push(`d.vehicle_id = COALESCE(d.vehicle_id, dd.vehicleId)`);
      }

      if (!updates.length) {
        return;
      }

      await queryInterface.sequelize.query(`
        UPDATE drivers d
        INNER JOIN driverdetails dd
          ON dd.userId = COALESCE(d.login_user_id, d.user_id)
        SET
          ${updates.join(',\n          ')}
        WHERE COALESCE(d.deleted, 0) = 0
      `);
    }
  },

  async down() {
    // Backfill only; no destructive rollback.
  },
};
