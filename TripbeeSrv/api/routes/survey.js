const api = require('express').Router();

module.exports = () => {
    api.post('/', async (req, res) => {
        const memory = req.body.conversation.memory;

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