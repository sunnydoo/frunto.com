const uuid = require('uuid/v4');
const api = require('express').Router();
const sapcai = require('sapcai').default;
//modify 

const CAI_KEY='181c1a2b3cd368754375e6f487cd6fd5';

//

//const build = new sapcai.build(process.env.CAI_KEY, 'zh');
const build = new sapcai.build(CAI_KEY, 'en');
const core = require('../libs/core');

module.exports = (io) => {
    api.post('/', async (req, res) => {
        const memory = req.body.memory || { prod: false };
        if (req.hostname.search("api.cfapps") !== -1) {
            memory.prod = true;
        }
        if (!req.body.sessionId) {
            req.body.sessionId = uuid();
            const user = req.body.user.search("Sebastian") !== -1 ? "A5DA9275012A428CAF7C3FF460DEA9A9" : "9233F470E300138A9D625FA65ABEE5F7";
            const serviceCall = await core.createServiceCall(false, user);
            memory.serviceCallId = serviceCall.id;
            memory.serviceCallCode = serviceCall.code;
           // memory.litmos = req.body.user.search("Sebastian") !== -1 ? "hNH5EEgTPhuCTulUU1950w2" : "k3gHdwsnhR-ZUUHyyQy50A2";
        }

        for (const message of req.body.messages) {
            io.emit('message', {
                user: 'Maintenance Assistant',
                time: req.body.time,
                device: req.body.device,
                bot: true,
                message,
                serviceCallCode: memory.serviceCallCode
            }, { for: 'everyone' });
        }

        io.emit('message', {
            user: req.body.user,
            time: req.body.time,
            device: req.body.device,
            bot: false,
            message: req.body.message,
            serviceCallCode: memory.serviceCallCode
        }, { for: 'everyone' });
        const content = await build.dialog({
            type: 'text',
            content: req.body.message
        }, { conversationId: req.body.sessionId, language: 'en' }, memory);  //change to cn,zh

        const messages = [];
        for (const message of content.messages) {
            if (message.type === "text") {
                messages.push(message.content);
                io.emit('message', {
                    user: 'Maintenance Assistant',
                    time: req.body.time,
                    device: req.body.device,
                    bot: true,
                    message: message.content,
                    serviceCallCode: memory.serviceCallCode,
                    done: content.conversation.memory.done || false
                }, { for: 'everyone' });
            }
        }

        res.send({
            id: content.conversation.id,
            memory: content.conversation.memory,
            messages,
        });
    });

    return api;
};
