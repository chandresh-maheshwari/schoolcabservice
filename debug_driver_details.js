const mongoose = require('mongoose');

// Connect to MongoDB
mongoose.connect('mongodb://127.0.0.1:27017/scb_app', {
    useNewUrlParser: true,
    useUnifiedTopology: true
}).then(async () => {
    console.log('Connected to MongoDB');

    try {
        // Define Schemas (simplified for reading)
        const User = mongoose.model('User', new mongoose.Schema({}, { strict: false }));
        const DriverDetails = mongoose.model('DriverDetails', new mongoose.Schema({}, { strict: false }));

        // 1. Get the specific user we are interested in
        const targetEmail = 'driver@gmail.com';
        const targetUser = await User.findOne({ email: targetEmail });

        console.log('\n--- TARGET USER ---');
        if (targetUser) {
            console.log(`Email: ${targetUser.email}`);
            console.log(`ID: ${targetUser._id.toString()}`);
        } else {
            console.log(`User with email ${targetEmail} NOT FOUND.`);
        }

        // 2. List ALL Driver Details
        const allDrivers = await DriverDetails.find({});
        console.log(`\n--- ALL DRIVER DETAILS (${allDrivers.length} found) ---`);

        allDrivers.forEach((d, index) => {
            console.log(`\nDriver #${index + 1}:`);
            console.log(`_id: ${d._id}`);
            console.log(`userId: ${d.userId ? d.userId.toString() : 'UNDEFINED'}`); // Check strictly
            console.log(`fullName: ${d.fullName}`);
            console.log(`vehicleNumber: ${d.vehicleNumber}`);

            // Check if this driver matches our target user
            if (targetUser && d.userId && d.userId.toString() === targetUser._id.toString()) {
                console.log('>>> MATCHES TARGET USER <<<');
            } else {
                console.log('>>> DOES NOT MATCH TARGET USER <<<');
            }
        });

    } catch (e) {
        console.error('Error:', e);
    } finally {
        mongoose.connection.close();
    }
}).catch(err => console.error(err));
