const fsm = require('fsm-sdk');
const moment = require('moment');
//modify 

//const AUTH_PASSWORD='s0vnGovWZKfcJpZDtiuO';
const CORE_KEY='uMqwS4uvwbFaV5EMbgCW';
const CORE_PASSWORD='yaqxswCDE:;321';
const CORE_SECRET='DSeGxa7GgDbeXGgqipdeCj3nt7dDvjMWY7Ea2ufx';
const CORE_USER='admin';
//const AUTH_USER='dt0EzSgZi3ARhc7FthsX';
//const PORT='9010'
//


const client = new fsm.CoreAPIClient({
    clientIdentifier: CORE_KEY,
    clientSecret: CORE_SECRET,
    clientVersion: '1.0.0',
    authAccountName: 'sap-cx-labs-handsfree',
    authUserName: CORE_USER,
    authPassword: CORE_PASSWORD,
    debug: false
});
const api = {
    getServiceCalls: () => new Promise(async (resolve) => {
        const time = moment().subtract(1, 'hours').utc().format('YYYY-MM-DD HH:mm:ss.SS');
        const sqlAll = `
        SELECT it
        FROM ServiceCall it
        where it.createDateTime < '${time}'
        `;
        resolve(client.query(sqlAll, ['ServiceCall']));
    }),
    createServiceCall: (phone, user) => new Promise(async (resolve) => {
        const startDateTime = new Date();
        const endDateTime = new Date(startDateTime.getTime() + (5 * 60 * 1000));

        const result = await client.post('ServiceCall', {
            id: fsm.CoreAPIClient.createUUID(),
            subject: 'Printer is not working',
            startDateTime: startDateTime.toISOString(),
            endDateTime: endDateTime.toISOString(),
            dueDateTime: endDateTime.toISOString(),
            statusCode: "-2",
            statusName: "Ready to plan",
            inactive: false,
            partOfRecurrenceSeries: false,
            contact: "CEEB99FFF377410288E34E713F640DB2",
            problemTypeCode: "-3",
            problemTypeName: "User Error",
            originCode: phone ? "-2" : "-4",
            originName: phone ? "Telephone" : "Apple Watch",
            typeCode: "-1",
            typeName: "Repair",
            priority: "MEDIUM",
            leader: user,
            responsibles: [user],
            businessPartner: "ABF824ECFAD4448EB1E2911FC40E94B0",
            equipments: ["2E94A74783DF46D697703F8DFED1EE38"],
            technicians: [user],
            chargeableMileages: false,
            chargeableMaterials: false,
            chargeableExpenses: false,
            chargeableEfforts: false,
            createDateTime: startDateTime.toISOString(),
        });
        resolve(result.data[0].serviceCall);
    }),
    getServiceCall: (id) => new Promise(async (resolve) => {
        resolve(await client.getById('ServiceCall', id));
    }),
    updateServiceCall: (id, data) => new Promise(async (resolve) => {
        const serviceCall = (await api.getServiceCall(id)).data[0].serviceCall;
        data.id = serviceCall.id;
        data.lastChanged = serviceCall.lastChanged;
        await client.patch('ServiceCall', data);
        resolve();
    }),
    deleteServiceCall: (id, lastChanged) => new Promise(async (resolve) => {
        await client.deleteById('ServiceCall', { id, lastChanged });
        resolve();
    }),
    getServiceAssignments: (serviceCallId) => new Promise(async (resolve) => {
        const sqlAll = `
        SELECT it
        FROM ServiceAssignment it
        where it.object.objectId = '${serviceCallId}'
        `;
        resolve(client.query(sqlAll, ['ServiceAssignment']));
    }),
    createServiceAssignment: (serviceCall) => new Promise(async (resolve) => {
        await client.post('ServiceAssignment', {
            id: fsm.CoreAPIClient.createUUID(),
            startDateTime: serviceCall.startDateTime,
            endDateTime: serviceCall.endDateTime,
            technician: serviceCall.leader,
            object: {
                objectId: serviceCall.id,
                objectType: 'SERVICECALL'
            }
        });
        resolve();
    }),
    deleteServiceAssignment: (id, lastChanged) => new Promise(async (resolve) => {
        await client.deleteById('ServiceAssignment', { id, lastChanged });
        resolve();
    }),
    getReservedMaterials: (serviceCallId) => new Promise(async (resolve) => {
        const sqlAll = `
        SELECT it
        FROM ReservedMaterial it
        where it.object.objectId = '${serviceCallId}'
        `;
        resolve(client.query(sqlAll, ['ReservedMaterial']));
    }),
    createReservedMaterial: (serviceCallId, itemId) => new Promise(async (resolve) => {
        await client.post('ReservedMaterial', {
            id: fsm.CoreAPIClient.createUUID(),
            warehouse: '289F290484B5422EB1F0CD4993E84982',
            item: itemId,
            quantity: 1,
            used: 1,
            object: {
                objectId: serviceCallId,
                objectType: 'SERVICECALL'
            }
        });
        resolve();
    }),
    deleteReservedMaterial: (id, lastChanged) => new Promise(async (resolve) => {
        await client.deleteById('ReservedMaterial', { id, lastChanged });
        resolve();
    }),
    getTimeEfforts: (serviceCallId) => new Promise(async (resolve) => {
        const sqlAll = `
        SELECT it
        FROM TimeEffort it
        where it.object.objectId = '${serviceCallId}'
        `;
        resolve(client.query(sqlAll, ['TimeEffort']));
    }),
    createTimeEffort: (serviceCallId, startDateTime) => new Promise(async (resolve) => {
        const endDateTime = (new Date()).toISOString();
        await client.post('TimeEffort', {
            id: fsm.CoreAPIClient.createUUID(),
            chargeOption: 'CHARGEABLE',
            startDateTimeTimeZoneId: 'America/Chicago',
            endDateTimeTimeZoneId: 'America/Chicago',
            breakInMinutes: 0,
            timeZoneId: 'UTC-05:00',
            createPerson: 'A5DA9275012A428CAF7C3FF460DEA9A9',
            startDateTime,
            endDateTime,
            createDateTime: endDateTime,
            object: {
                objectId: serviceCallId,
                objectType: 'SERVICECALL'
            }
        });
        resolve();
    }),
    deleteTimeEffort: (id, lastChanged) => new Promise(async (resolve) => {
        await client.deleteById('TimeEffort', { id, lastChanged });
        resolve();
    }),
    getMaterials: (serviceCallId) => new Promise(async (resolve) => {
        const sqlAll = `
        SELECT it
        FROM Material it
        where it.object.objectId = '${serviceCallId}'
        `;
        resolve(client.query(sqlAll, ['Material']));
    }),
    createMaterial: (serviceCallId, itemId) => new Promise(async (resolve) => {
        await client.post('Material', {
            id: fsm.CoreAPIClient.createUUID(),
            chargeOption: 'CHARGEABLE',
            date: (new Date()).toISOString(),
            warehouse: '289F290484B5422EB1F0CD4993E84982',
            quantity: 1,
            item: itemId,
            object: {
                objectId: serviceCallId,
                objectType: 'SERVICECALL'
            }
        });
        resolve();
    }),
    deleteMaterial: (id, lastChanged) => new Promise(async (resolve) => {
        await client.deleteById('Material', { id, lastChanged });
        resolve();
    }),
    getActivities: () => new Promise(async (resolve) => {
        const sqlAll = `
        SELECT it
        FROM Activity it
        `;
        resolve(client.query(sqlAll, ['Activity']));
    }),
    createActivity: (serviceCallId) => new Promise(async (resolve) => {
        await client.post('Activity', {
            id: fsm.CoreAPIClient.createUUID(),
            object: {
                objectId: serviceCallId,
                objectType: 'SERVICECALL'
            },
            subject: 'Printer is not working',
            status: "DRAFT",
            startDateTime: startDateTime.toISOString(),
            endDateTime: endDateTime.toISOString(),
            createDateTime: startDateTime.toISOString(),
            earliestStartDateTime: startDateTime.toISOString(),
            dueDateTime: endDateTime.toISOString(),
            executionStage: "DISPATCHING",
            responsibles: ["A5DA9275012A428CAF7C3FF460DEA9A9"],
            travelTimeFromInMinutes: 0,
            travelTimeToInMinutes: 0
        });
        resolve();
    }),
    deleteActivity: (id, lastChanged) => new Promise(async (resolve) => {
        await client.deleteById('Activity', { id, lastChanged });
        resolve();
    }),
    createStockTransfer: (serviceCallId, itemId) => new Promise(async (resolve) => {
        await client.post('StockTransfer', {
            id: fsm.CoreAPIClient.createUUID(),
            fromWarehouse: '52389581BF1C441480DFC3D78B46FFF8',
            toWarehouse: '289F290484B5422EB1F0CD4993E84982',
            inventoryItems: [itemId],
            object: {
                objectId: serviceCallId,
                objectType: 'SERVICECALL'
            }
        });
        resolve();
    }),
    getParts: (name) => new Promise(async (resolve) => {
        const sqlFind = `
        SELECT it.id, it.name
        FROM Item it 
        WHERE it.groupCode = 'ZSPARE' and Upper(it.name) LIKE '%${name.toUpperCase()}%'
        `;
        const sqlAll = `
        SELECT it.name
        FROM Item it 
        WHERE it.groupCode = 'ZSPARE'
        `;
        const result = await Promise.all([
            client.query(sqlFind, ['Item']),
            client.query(sqlAll, ['Item']),
        ]);
        resolve(result);
    })
};
module.exports = api;