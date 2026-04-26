const axios = require('axios');

async function testCheck() {
    const token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
    try {
        const res = await axios.get('https://api.zendit.io/v1/esim/purchases/qoon_3210', {
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
testCheck();
