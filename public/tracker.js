(async function (){
    const currScript = document.currentScript, debugMode = currScript.getAttribute('data-debug');

    if (debugMode) {
        console.log('Tracker running in debug mode. Consider disabling it when running in production.')
    }

    try {
        const apiToken = currScript.getAttribute('data-token');

        if (!apiToken) {
            throw Error('API Token is missing.');
        }

        const currPageUrl = window.location.href;
        const cookieKey = currScript.getAttribute('data-cookie') ?? 'trackedId';
        let trackedId = getCookie(cookieKey);

        if (!trackedId) {
            trackedId = crypto.randomUUID();
            setCookie(cookieKey, trackedId);
        }

        const response = await fetch('http://localhost:8080/api/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Token': apiToken
            },
            body: JSON.stringify({
                pageUrl: currPageUrl,
                trackedId: trackedId,
                timestamp: new Date().toISOString(),
            })
        });

        if (!response.ok) {
            throw new Error(`API HTTP Error Status: ${response.status}`)
        }

        const result = await response.json();

        if (debugMode) {
            console.log(result);
        }
        return true;
    } catch (error) {
        if (debugMode) {
            console.error("Traffic Tracker error: ", error);
        }
    }

    // Cookie getter function
    function getCookie(name) {
        const parts = `; ${document.cookie}`.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
    }

    // Cookie setter function
    function setCookie(name, value) {
        const expirationDate = new Date();
        expirationDate.setFullYear(expirationDate.getFullYear() + 1);
        document.cookie = `${name}=${value}; expires=${expirationDate.toUTCString()}; path=/`;
    }
})();