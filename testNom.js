const axios = require('axios');
async function run() {
    try {
        const res = await axios.get('https://nominatim.openstreetmap.org/search?q=hotel+in+Paris&format=json&limit=5', {
            headers: { 'User-Agent': 'QOON-App/1.0 (contact@qoon.app)' }
        });
        console.log(res.data.map(h => h.name));
    } catch(e) {
        console.error(e.message);
    }
}
run();
