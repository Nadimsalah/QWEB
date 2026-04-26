const axios = require('axios');

async function testSwagger() {
    const urls = [
        'https://api.zendit.io/v1/swagger.json',
        'https://api.zendit.io/swagger/v1/swagger.json',
        'https://api.zendit.io/swagger.json',
        'https://api.zendit.io/openapi.json'
    ];

    for (let url of urls) {
        try {
            const res = await axios.get(url);
            console.log("FOUND AT:", url);
            const paths = Object.keys(res.data.paths);
            console.log(paths.filter(p => p.includes('esim') && p.includes('transaction')));
            break;
        } catch(e) {
            console.log("Failed:", url);
        }
    }
}
testSwagger();
