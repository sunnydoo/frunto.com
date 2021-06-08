const cors = require('cors');
const axios = require('axios');
const app = require('express')();
const server = require('http').Server(app);
const io = require('socket.io')(server);
const bodyParser = require('body-parser');
const cookieParser = require('cookie-parser');
const urlencoded = require('body-parser').urlencoded;
const routes = require('./routes');
const core = require('./libs/core');
//modify
const LITMOS_KEY='c96e6bc2-4e3c-4e59-916c-ccaf712421e1';
const PORT='8081'
//
app.use(cors());
app.use(cookieParser());
app.use(bodyParser.json());
app.use(urlencoded({ extended: false }));
app.use('/', routes(io));

const cleanCoreSystems = () => {
    core.getServiceCalls().then(async results => {
        for (const serviceCall of results.data) {
            const reservedMaterials = await core.getReservedMaterials(serviceCall.it.id);
            for (const reservedMaterial of reservedMaterials.data) {
                await core.deleteReservedMaterial(reservedMaterial.it.id, reservedMaterial.it.lastChanged)
            }
            const materials = await core.getMaterials(serviceCall.it.id);
            for (const material of materials.data) {
                await core.deleteMaterial(material.it.id, material.it.lastChanged)
            }
            const timeEfforts = await core.getTimeEfforts(serviceCall.it.id);
            for (const timeEffort of timeEfforts.data) {
                await core.deleteTimeEffort(timeEffort.it.id, timeEffort.it.lastChanged)
            }
            const serviceAssignments = await core.getServiceAssignments(serviceCall.it.id);
            for (const serviceAssignment of serviceAssignments.data) {
                await core.deleteServiceAssignment(serviceAssignment.it.id, serviceAssignment.it.lastChanged)
            }

            await core.deleteServiceCall(serviceCall.it.id, serviceCall.it.lastChanged);
        }

        setTimeout(() => {
            cleanCoreSystems();
        }, 60000);
    });
}
const cleanLitmos = () => {
    setTimeout(async () => {
        const key = LITMOS_KEY;
        const url = 'https://api.litmos.com/v1.svc/';
        const courceId = 'DzjlvpDFZ9M1';

        const users = await axios({
            method: 'GET',
            url: `${url}/courses/${courceId}/users?apikey=${key}&source=api&format=json`
        });
        for (const user of users.data) {
            await axios({
                method: 'DELETE',
                url: `${url}/users/${user.Id}/courses/${courceId}?apikey=${key}&source=api`
            });
        }
        cleanLitmos();
    }, 60000 * 5);
};
cleanCoreSystems();
cleanLitmos();

server.listen(PORT || 8081, null, null, () => {
    console.log('Api is running!');
});