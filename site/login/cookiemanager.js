export const setCookie = (cname, cvalue, exdays) => {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

export const getCookie = (cname) => {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

export const isCookieValid = (cname) => {
    const cookies = document.cookie.split(";");
    // forEach yerine for...of veya normal for kullanıyoruz ki return yapabilelim
    for (const cookie of cookies) {
        if (cookie.trim().startsWith(cname + "=")) {
            return true; // Çerez bulundu, true döndür
        }
    }
    return false; // Hiçbir eşleşme olmazsa false döndür
}