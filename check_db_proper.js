const mongoose = require('mongoose');
const User = require('./models/User');

async function check() {
    await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');

    const users = await User.find({});
    users.forEach(u => {
        console.log(`Email: ${u.email}, Role: ${u.role}, Pwd: ${u.password.substring(0, 5)}...`);
    });

    mongoose.connection.close();
}

check();
