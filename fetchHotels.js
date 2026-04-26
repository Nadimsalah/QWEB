const axios = require('axios');

async function fetchRealHotelData() {
    const endpoint = 'https://engine.hotellook.com/api/v2/cache.json';
    const token = '0ca3dc3467606e4a114830217d4adf73'; // Your Travelpayouts token
    
    try {
        const response = await axios.get(endpoint, {
            params: {
                location: 'Paris',
                currency: 'USD',
                checkIn: '2026-05-10',
                checkOut: '2026-05-15',
                adults: 2,
                limit: 10,
                token: token
            }
        });

        // Hotellook Cache API returns an array of hotel objects
        const hotels = response.data.map(hotel => ({
            hotelName: hotel.hotelName || 'Unknown Hotel',
            price: hotel.priceFrom || hotel.priceAvg || hotel.price || 'N/A',
            stars: hotel.stars || 0,
            location: hotel.location || 'Paris',
            hotelId: hotel.hotelId || hotel.id
        }));

        console.log("Successfully fetched real hotel data:");
        console.log(JSON.stringify(hotels, null, 2));
        
        return hotels;

    } catch (error) {
        console.error("Error fetching hotel data:", error.response ? error.response.data : error.message);
        return null;
    }
}

// Example function call
fetchRealHotelData();
