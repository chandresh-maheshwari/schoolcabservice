const { Sequelize } = require('sequelize');

const database = process.env.DB_NAME || 'm_cab';
const username = process.env.DB_USER || 'root';
const password = process.env.DB_PASSWORD || '';
const host = process.env.DB_HOST || 'localhost';
const port = process.env.DB_PORT ? Number(process.env.DB_PORT) : undefined;

const sequelize = new Sequelize(database, username, password, {
  host,
  port,
  dialect: 'mysql',
  logging: false,
});

const connectDB = async () => {
  try {
    await sequelize.authenticate();
    console.log(`MySQL connected successfully: ${database}@${host}`);
  } catch (error) {
    console.error('Unable to connect to the MySQL database:', error);
  }
};

module.exports = { sequelize, connectDB };
