const mongoose = require('mongoose');
const User = require('./models/User');
const bcrypt = require('bcryptjs');

async function testLogin() {
    await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');

    const email = 'driver@example.com';
    const password = 'password123';

    const user = await User.findOne({ email });
    if (!user) {
        console.log('User not found in DB');
        mongoose.connection.close();
        return;
    }

    console.log('DB Password for', email, ':', user.password);

    const isMatch = await bcrypt.compare(password, user.password);
    console.log('Bcrypt Match Result:', isMatch);

    mongoose.connection.close();
}

testLogin();
