const mongoose = require('mongoose');
const Trip = require('./models/Trip');

async function check() {
    await mongoose.connect('mongodb://127.0.0.1:27017/scb_app');
    const trip = await Trip.findOne({ status: 'running' });
    if (!trip) {
        console.log('No running trip found.');
        const all = await Trip.find({});
        console.log('All trips count:', all.length);
    } else {
        console.log('Trip found:', {
            status: trip.status,
            stops: trip.stops.length,
            nextStop: trip.nextStop?.name,
            routePoints: trip.currentRoute?.points?.length || 0
        });
    }
    process.exit();
}
check();
