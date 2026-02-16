const mongoose = require('mongoose');

mongoose.connect('mongodb://127.0.0.1:27017/scb_app', {
    useNewUrlParser: true,
    useUnifiedTopology: true
}).then(async () => {
    console.log('Connected to MongoDB');

    try {
        const User = mongoose.model('User', new mongoose.Schema({
            email: { type: String, required: true, unique: true },
            password: { type: String, required: true },
            role: { type: String, required: true }
        }));

        const DriverDetails = mongoose.model('DriverDetails', new mongoose.Schema({
            userId: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true, unique: true },
            fullName: String,
            vehicleNumber: String,
            stops: Array
        }));

        const email = 'driver@gmail.com';
        const user = await User.findOne({ email });
        console.log('User found:', user);

        if (user) {
            const driverDetails = await DriverDetails.findOne({ userId: user._id });
            console.log('DriverDetails found:', driverDetails);
        }

    } catch (e) {
        console.error(e);
    } finally {
        mongoose.connection.close();
    }
}).catch(err => console.error(err));
