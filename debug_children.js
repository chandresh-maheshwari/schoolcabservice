const mongoose = require('mongoose');
const Child = require('./models/Child');
const User = require('./models/User');

mongoose.connect('mongodb://127.0.0.1:27017/scb_app')
    .then(async () => {
        console.log('Connected to DB');
        const children = await Child.find({});
        console.log('Children:', JSON.stringify(children, null, 2));
        mongoose.disconnect();
    })
    .catch(err => {
        console.error('DB Connection Error:', err);
        process.exit(1);
    });
