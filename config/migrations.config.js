module.exports = {
  development: {
    username: 'root',
    password: '',
    database: 'u262996382_schoolcab_stag',
    host: '127.0.0.1',
    dialect: 'mysql',
    logging: false,
  },
  test: {
    username: 'root',
    password: '',
    database: 'scb_app_test',
    host: '127.0.0.1',
    dialect: 'mysql',
    logging: false,
  },
  production: {
    use_env_variable: 'DATABASE_URL',
    dialect: 'mysql',
    logging: false,
  },
};

