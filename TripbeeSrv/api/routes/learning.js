const axios = require('axios');
const api = require('express').Router();


//modify
const LITMOS_KEY='c96e6bc2-4e3c-4e59-916c-ccaf712421e1';

//

module.exports = () => {
    api.post('/', async (req, res) => {
        const memory = req.body.conversation.memory;
        const key = LITMOS_KEY;
        const url = 'https://api.litmos.com/v1.svc/users';
        const courceId = 'DzjlvpDFZ9M1';

        await axios({
            method: 'POST',
            data: [{
                Id: courceId
            }],
            url: `${url}/${memory.litmos}/courses?apikey=${key}&source=api`
        });

        res.send({
            replies: [{
                type: "text",
                content: "Sends out registration confirmation."
            }],
            conversation: {
                language: "en",
                memory
            }
        });
    });

    return api;
};