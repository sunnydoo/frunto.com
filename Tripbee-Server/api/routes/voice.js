const uuid = require('uuid/v4');
const core = require('../libs/core');
const sapcai = require('sapcai').default;
const api = require('express').Router();
const VoiceResponse = require('twilio').twiml.VoiceResponse;

//modify 

const CAI_KEY='181c1a2b3cd368754375e6f487cd6fd5';

//

const voiceSettings = {
    voice: 'woman',
};
const speechSettings = {
    input: 'speech',
    speechTimeout: 1,
    language: 'en-US',
    profanityFilter: false,
    speechModel: 'phone_call',
    timeout: 30
};
const build = new sapcai.build(CAI_KEY, 'en');

module.exports = (io) => {
    api.post('/', async (req, res) => {
        const twiml = new VoiceResponse();
        console.log(req.body.SpeechResult);
        if (req.cookies['session']) {
            const memory = req.cookies['session'].memory || { prod: true };

            if (!req.cookies['session'].auth) {
                if (req.body.SpeechResult === "sap") {
                    const serviceCall = await core.createServiceCall(true, 'A5DA9275012A428CAF7C3FF460DEA9A9');
                    memory.serviceCallId = serviceCall.id;
                    memory.serviceCallCode = serviceCall.code;
                    memory.litmos = "hNH5EEgTPhuCTulUU1950w2";

                    const message = 'Hi Sebastian. For your current appointment, do you need guided maintenance or do you wish to order a spare part?';
                    const gather = twiml.gather(speechSettings);
                    gather.say(voiceSettings, message);
                    res.cookie('session', {
                        id: req.cookies['session'].id,
                        memory,
                        auth: true
                    });
                    io.emit('message', {
                        user: 'Maintenance Assistant',
                        time: new Date().getTime(),
                        device: 'phone',
                        bot: true,
                        message,
                        serviceCallCode: memory.serviceCallCode
                    }, { for: 'everyone' });
                } else {
                    const message = 'Sorry the securecode was not correct! Goodbye!';
                    io.emit('message', {
                        user: 'Maintenance Assistant',
                        time: new Date().getTime(),
                        device: req.body.device,
                        bot: true,
                        message
                    }, { for: 'everyone' });
                    twiml.say(voiceSettings, message);
                    twiml.hangup();
                }
            } else {
                io.emit('message', {
                    user: 'Jon',
                    time: new Date().getTime(),
                    device: 'phone',
                    bot: false,
                    message: req.body.SpeechResult,
                    serviceCallCode: memory.serviceCallCode
                }, { for: 'everyone' });

                const content = await build.dialog({
                    type: 'text',
                    content: req.body.SpeechResult
                }, { conversationId: req.cookies['session'].id, language: 'en' }, memory);

                let gather = null;
                if (!content.conversation.memory.done) {
                    gather = twiml.gather(speechSettings);
                }
                res.cookie('session', {
                    id: req.cookies['session'].id,
                    memory: content.conversation.memory,
                    auth: true
                });

                for (const message of content.messages) {
                    if (message.type === "text") {
                        io.emit('message', {
                            user: 'Maintenance Assistant',
                            time: new Date().getTime(),
                            device: 'phone',
                            bot: true,
                            message: message.content,
                            serviceCallCode: memory.serviceCallCode,
                            done: content.conversation.memory.done || false
                        }, { for: 'everyone' });

                        if (!content.conversation.memory.done) {
                            gather.say(voiceSettings, message.content);
                        } else {
                            twiml.say(voiceSettings, message.content);
                        }
                    }
                }

                if (content.conversation.memory.done) {
                    twiml.hangup();
                }
            }
        } else {
            res.cookie('session', {
                id: uuid(),
                auth: false
            });
            speechSettings.hints = "sap";
            const message = 'Hello this is Maintenance Assistant. Please provide your securecode?';
            const gather = twiml.gather(speechSettings);
            gather.say(voiceSettings, message);
            io.emit('message', {
                user: 'Maintenance Assistant',
                time: new Date().getTime(),
                device: 'phone',
                bot: true,
                message
            }, { for: 'everyone' });
        }

        res.type('text/xml');
        res.send(twiml.toString());
    });

    return api;
};