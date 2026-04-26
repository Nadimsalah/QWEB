const axios = require('axios');

async function testZendit() {
    const token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
    try {
        const res = await axios.get('https://api.zendit.io/v1/esim/offers?_limit=1&_offset=0&country=MA', {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        console.log(JSON.stringify(res.data.list[0], null, 2));
    } catch(e) {
        console.log("Failed:", e.response ? e.response.data : e.message);
    }
}
testZendit();
