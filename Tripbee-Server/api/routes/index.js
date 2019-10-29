const api = require('express').Router();
const basicAuth = require('express-basic-auth');

const voice = require('./voice');
const parts = require('./parts');
const dialog = require('./dialog');
const ticket = require('./ticket');
const survey = require('./survey');
const learning = require('./learning');
//modify

//modify 

const AUTH_PASSWORD='s0vnGovWZKfcJpZDtiuO';
//const AUTH_USER='dt0EzSgZi3ARhc7FthsX';
//const PORT='9010'
//

module.exports = (io) => {
    const users = {};
   // users[process.env.AUTH_USER] = process.env.AUTH_PASSWORD;
    users[process.env.AUTH_USER] = AUTH_PASSWORD;

   // api.use('/parts', basicAuth({ users }), parts());
	api.use('/parts',  parts());
    api.use('/voice', voice(io));
   // api.use('/dialog', basicAuth({ users }), dialog(io));
	api.use('/dialog', dialog(io));
    api.use('/ticket', basicAuth({ users }), ticket());
    api.use('/survey', basicAuth({ users }), survey());
    api.use('/learning', basicAuth({ users }), learning());
    api.post('/reset', (req, res) => {
        io.emit('message', {
            reset: true
        }, { for: 'everyone' });
        res.sendStatus(200);
    });

    return api;
};