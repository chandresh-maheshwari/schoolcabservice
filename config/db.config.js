const path = require('path');
const { Sequelize } = require('sequelize');

require('dotenv').config({ path: path.join(__dirname, '..', '.env') });

const database =
  process.env.DB_DATABASE ||
  process.env.DB_NAME ||
  'M_cab';
const username = process.env.DB_USER || process.env.DB_USERNAME || 'root';
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
