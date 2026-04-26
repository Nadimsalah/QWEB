const axios = require('axios');

async function testPurchase() {
    const token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
    try {
        const res = await axios.post('https://api.zendit.io/v1/esim/purchases', {
            offerId: "ESIM-MA-1D-UNLIMITED-NOROAM",
            transactionId: "qoon_" + Math.floor(Math.random()*10000)
        }, {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        console.log("SUCCESS:", JSON.stringify(res.data, null, 2));
    } catch(e) {
        console.log("Failed:", e.response ? e.response.data : e.message);
    }
}
testPurchase();
