const database = process.env.DB_NAME || 'm_cab';
const username = process.env.DB_USER || 'root';
const password = process.env.DB_PASSWORD || '';
const host = process.env.DB_HOST || '127.0.0.1';
const port = process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306;

module.exports = {
  development: {
    username,
    password,
    database,
    host,
    port,
    dialect: 'mysql',
    logging: false,
  },
  test: {
    username,
    password,
    database: process.env.DB_NAME_TEST || 'scb_app_test',
    host,
    port,
    dialect: 'mysql',
    logging: false,
  },
  production: {
    use_env_variable: 'DATABASE_URL',
    dialect: 'mysql',
    logging: false,
  },
};
