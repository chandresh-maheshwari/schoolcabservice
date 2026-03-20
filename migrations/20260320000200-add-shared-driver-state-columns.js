'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!tables.includes('drivers')) {
      return;
    }

    const description = await queryInterface.describeTable('drivers');

    const addColumnIfMissing = async (columnName, definition) => {
      if (description[columnName]) return;
      await queryInterface.addColumn('drivers', columnName, definition);
    };

    await addColumnIfMissing('current_lat', {
      type: Sequelize.DOUBLE,
      allowNull: true,
    });

    await addColumnIfMissing('current_lng', {
      type: Sequelize.DOUBLE,
      allowNull: true,
    });

    await addColumnIfMissing('vehicle_number', {
      type: Sequelize.STRING,
      allowNull: true,
    });

    await addColumnIfMissing('vehicle_model', {
      type: Sequelize.STRING,
      allowNull: true,
    });

    await addColumnIfMissing('vehicle_capacity', {
      type: Sequelize.INTEGER,
      allowNull: true,
    });

    await addColumnIfMissing('stops_json', {
      type: Sequelize.JSON,
      allowNull: true,
    });

    await addColumnIfMissing('current_route_json', {
      type: Sequelize.JSON,
      allowNull: true,
    });

    await addColumnIfMissing('last_completed_stop_index', {
      type: Sequelize.INTEGER,
      allowNull: false,
      defaultValue: -1,
    });

    if (tables.includes('driverdetails')) {
      await queryInterface.sequelize.query(`
        UPDATE drivers d
        INNER JOIN driverdetails dd
          ON dd.userId = COALESCE(d.login_user_id, d.user_id)
        SET
          d.current_lat = COALESCE(d.current_lat, dd.currentLat),
          d.current_lng = COALESCE(d.current_lng, dd.currentLng),
          d.vehicle_number = COALESCE(NULLIF(d.vehicle_number, ''), dd.vehicleNumber),
          d.vehicle_model = COALESCE(NULLIF(d.vehicle_model, ''), dd.vehicleModel),
          d.vehicle_capacity = COALESCE(d.vehicle_capacity, dd.vehicleCapacity),
          d.stops_json = COALESCE(d.stops_json, dd.stops),
          d.current_route_json = COALESCE(d.current_route_json, dd.currentRoute),
          d.last_completed_stop_index = COALESCE(d.last_completed_stop_index, dd.lastCompletedStopIndex, -1)
        WHERE COALESCE(d.deleted, 0) = 0
      `);
    }
  },

  async down(queryInterface) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!tables.includes('drivers')) {
      return;
    }

    const description = await queryInterface.describeTable('drivers');
    const dropColumnIfExists = async (columnName) => {
      if (!description[columnName]) return;
      await queryInterface.removeColumn('drivers', columnName);
    };

    await dropColumnIfExists('last_completed_stop_index');
    await dropColumnIfExists('current_route_json');
    await dropColumnIfExists('stops_json');
    await dropColumnIfExists('vehicle_capacity');
    await dropColumnIfExists('vehicle_model');
    await dropColumnIfExists('vehicle_number');
    await dropColumnIfExists('current_lng');
    await dropColumnIfExists('current_lat');
  },
};
