const mongoose = require('mongoose');

mongoose.connect('mongodb://127.0.0.1:27017/scb_app', {
    useNewUrlParser: true,
    useUnifiedTopology: true
}).then(async () => {
    console.log('Connected to MongoDB');

    try {
        const User = mongoose.model('User', new mongoose.Schema({}, { strict: false }));
        // Must allow flexible schema to read the document even if it's missing fields, 
        // but we want to update it to include userId.
        const DriverDetails = mongoose.model('DriverDetails', new mongoose.Schema({
            userId: mongoose.Schema.Types.ObjectId,
            fullName: String
        }, { strict: false }));

        const email = 'driver@gmail.com';
        const user = await User.findOne({ email });

        if (!user) {
            console.error('User not found!');
            return;
        }

        console.log(`Found User: ${user.email} (ID: ${user._id})`);

        // Find the orphaned driver details (assuming there's only one or the one with name "Driver")
        // Alternatively, update ALL driver details that don't have a userId? 
        // Let's target the one from the screenshot/debug: "Driver"

        const driver = await DriverDetails.findOne({ fullName: 'Driver' });

        if (driver) {
            console.log(`Found Driver Document: ${driver._id}`);
            console.log(`Current userId: ${driver.userId}`);

            driver.userId = user._id; // Link them!
            await driver.save();

            console.log('SUCCESS: Linked DriverDetails to User.');
        } else {
            console.log('No DriverDetails document found with fullName "Driver".');
            // Fallback: check if there is ANY driver details
            const anyDriver = await DriverDetails.findOne({});
            if (anyDriver) {
                console.log(`Found a driver detail with id ${anyDriver._id} but name is ${anyDriver.fullName}. Linking this one.`);
                anyDriver.userId = user._id;
                await anyDriver.save();
                console.log('SUCCESS: Linked DriverDetails to User.');
            }
        }

    } catch (e) {
        console.error(e);
    } finally {
        mongoose.connection.close();
    }
}).catch(err => console.error(err));
