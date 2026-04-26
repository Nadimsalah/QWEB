const axios = require('axios');
const crypto = require('crypto');

async function testApi() {
    const token = '0ca3dc3467606e4a114830217d4adf73';
    const marker = '521631';
    const adultsCount = 2;
    const checkIn = '2026-05-10';
    const checkOut = '2026-05-15';
    const childrenCount = 0;
    const currency = 'USD';
    const customerIP = '8.8.8.8';
    const iata = 'PAR';
    const lang = 'en';
    const timeout = 20;
    const waitForResult = 0;

    // token:marker:adultsCount:checkIn:checkOut:childrenCount:currency:customerIP:iata:lang:timeout:waitForResult
    const sigStr = `${token}:${marker}:${adultsCount}:${checkIn}:${checkOut}:${childrenCount}:${currency}:${customerIP}:${iata}:${lang}:${timeout}:${waitForResult}`;
    const signature = crypto.createHash('md5').update(sigStr).digest('hex');

    try {
        const res = await axios.post('https://api.travelpayouts.com/v1/hotels/search', {
            marker: marker,
            adultsCount: adultsCount,
            checkIn: checkIn,
            checkOut: checkOut,
            childrenCount: childrenCount,
            currency: currency,
            customerIP: customerIP,
            iata: iata,
            lang: lang,
            timeout: timeout,
            waitForResult: waitForResult,
            signature: signature
        }, {
            headers: { 'Content-Type': 'application/json' }
        });
        console.log("SUCCESS:", res.data);
    } catch (e) {
        console.log("ERROR:", e.response ? e.response.data : e.message);
    }
}
testApi();
