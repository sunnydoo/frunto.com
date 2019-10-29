const api = require('express').Router();
const core = require('../libs/core');

module.exports = () => {
    api.post('/', async (req, res) => {
      //  const result = await core.getParts(req.body.nlp.source.toUpperCase());
		 const result = await core.getParts('TONER');
        const memory = req.body.conversation.memory;

        const replies = [];
        if (result[0].data.length === 1) {
            memory.part = result[0].data[0].it.id;
            await core.createMaterial(memory.serviceCallId, memory.part);
            replies.push({
                type: "text",
               // content: `Part is added to your service request.`
				content: `部件已添加到服务请求中.`
            });
        } else if (result[0].data.length === 0) {
            const parts = [];
            for (const part of result[1].data) {
                parts.push(part.it.name.replace("HP Color LaserJet E57540 ", ""));
            }

            replies.push({
                type: "text",
                content: "部件无法识别 但可以得到下面的选择" + parts.toString().replace(",", ", ")
            });
        } else if (result[0].data.length > 1) {
            const parts = [];
            for (const part of result[0].data) {
                parts.push(part.it.name.replace("HP Color LaserJet E57540 ", ""));
            }

            replies.push({
                type: "text",
                content: `下面有 ${result[0].data.length} 你想要哪个备件: ${parts.toString().replace(",", ", ")}`
            });
        }

        res.send({
            replies,
            conversation: {
                language: "zh",
                memory
            }
        });
    });

    return api;
};