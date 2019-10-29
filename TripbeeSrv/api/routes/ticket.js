const api = require('express').Router();
const core = require('../libs/core');

module.exports = () => {
    api.post('/', async (req, res) => {
        console.log('Create service');
        const memory = req.body.conversation.memory;
        const serviceCall = await core.createServiceCall(false, 'A5DA9275012A428CAF7C3FF460DEA9A9');
        await core.createReservedMaterial(serviceCall.id, 'B3528EF5EBA945F6AA5919DFFBA30E5C');

        res.send({
            replies: [{
                type: "text",
                content: `Service request ${serviceCall.code} created.`
            }],
            conversation: {
                language: "en",
                memory
            }
        });
    });

    api.patch('/', async (req, res) => {
        const memory = req.body.conversation.memory;
        await core.updateServiceCall(memory.serviceCallId, { remarks: req.body.nlp.source });

        const replies = [];
        res.send({
            replies,
            conversation: {
                language: "en",
                memory
            }
        });
    });

    api.put('/', async (req, res) => {
        const memory = req.body.conversation.memory;
        const serviceCall = (await core.getServiceCall(memory.serviceCallId)).data[0].serviceCall;
        await core.updateServiceCall(memory.serviceCallId, {
            statusCode: "-1",
            statusName: "Closed",
        });
        await core.createTimeEffort(serviceCall.id, serviceCall.createDateTime);

        const replies = [];
        res.send({
            replies,
            conversation: {
                language: "en",
                memory
            }
        });
    });

    return api;
};

