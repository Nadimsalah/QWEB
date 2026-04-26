const axios = require('axios');

async function test() {
    try {
        const res = await axios.get('https://api.travelpayouts.com/hotellook/v1/hotels?location_id=13157a99-2646-4945-8f7e-71d6a88da380', {
            headers: { 'X-Access-Token': '0ca3dc3467606e4a114830217d4adf73' }
        });
        console.log("Success! Got data.");
        console.log(JSON.stringify(res.data).substring(0, 500));
    } catch (e) {
        console.error("Failed:", e.response ? e.response.data : e.message);
    }
}
test();
