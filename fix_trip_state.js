const mongoose = require('mongoose');
const Trip = require('./models/Trip');
const Child = require('./models/Child');

mongoose.connect('mongodb://127.0.0.1:27017/scb_app')
    .then(async () => {
        console.log('Cleaning up Trip Status...');

        // 1. Delete all active trips
        await Trip.deleteMany({});
        console.log('Deleted all active trips.');

        // 2. Reset all children to 'pending'
        await Child.updateMany({}, { tripStatus: 'pending' });
        console.log('Reset all children to pending status.');

        console.log('State fixed. You can start a new trip.');
        mongoose.disconnect();
        process.exit(0);
    })
    .catch(err => {
        console.error(err);
        process.exit(1);
    });
