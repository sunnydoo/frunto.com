const axios = require('axios');
const app = require('express')();
const bodyParser = require('body-parser');
const basicAuth = require('express-basic-auth');


//modify 

const AUTH_PASSWORD='s0vnGovWZKfcJpZDtiuO';
const AUTH_USER='dt0EzSgZi3ARhc7FthsX';
const PORT='9010'
//
const users = {};
//users[process.env.AUTH_USER] = process.env.AUTH_PASSWORD;

app.use(bodyParser.json());
app.use(basicAuth({ users }));
app.use(async (req, res) => {
   // let url = 'https://hands-free-fieldservice-api-dev.cfapps.eu10.hana.ondemand.com';
   let url = 'https://localhost';
    if (req.body.conversation.memory && req.body.conversation.memory.prod) {
       // url = 'https://hands-free-fieldservice-api.cfapps.eu10.hana.ondemand.com';
       url = 'https://localhost';
    }
    res.send((await axios({
        method: req.method,
        auth: {
           // username: process.env.AUTH_USER,
           // password: process.env.AUTH_PASSWORD
           username: AUTH_USER,
           password: AUTH_PASSWORD
        },
        data: req.body,
        url: `${url}${req.originalUrl}`
    })).data);
});

//app.listen(process.env.PORT || 8080, () => {
    app.listen(PORT || 9100, () => {
    console.log('Proxy is running!');
});