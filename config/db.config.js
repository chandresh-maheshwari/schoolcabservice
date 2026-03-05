const { Sequelize } = require('sequelize');

const sequelize = new Sequelize('u262996382_schoolcab_stag', 'root', '', {
    host: 'localhost',
    dialect: 'mysql',
    logging: false, // Set to true if you want to see SQL queries in the console
});

const connectDB = async () => {
    try {
        await sequelize.authenticate();
        console.log('✅ MySQL connected successfully with Sequelize');

        // Sync models - in production you might want to use migrations
        // await sequelize.sync({ alter: true }); 
    } catch (error) {
        console.error('❌ Unable to connect to the MySQL database:', error);
    }
};

module.exports = { sequelize, connectDB };
