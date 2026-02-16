const mongoose = require('mongoose');
const User = require('./models/User');

async function updateAdminPassword() {
    try {
        await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');
        console.log('Connected to MongoDB');

        const result = await User.updateOne(
            { role: 'admin' },
            { $set: { password: 'admin123' } }
        );

        if (result.matchedCount > 0) {
            console.log('Successfully updated admin password to: admin123');
        } else {
            console.log('No admin user found to update.');
        }
    } catch (error) {
        console.error('Error updating password:', error);
    } finally {
        await mongoose.disconnect();
        console.log('Disconnected from MongoDB');
    }
}

updateAdminPassword();
