const mongoose = require('mongoose');

mongoose.connect('mongodb://127.0.0.1:27017/scb_app', {
    useNewUrlParser: true,
    useUnifiedTopology: true
}).then(async () => {
    console.log('MongoDB Connected');
    const users = await mongoose.connection.db.collection('users').find({}).toArray();
    console.log('Users in DB:');
    console.log(JSON.stringify(users, null, 2));
    mongoose.disconnect();
}).catch(err => console.log(err));
