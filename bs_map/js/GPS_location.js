

/**
 * GPS functions
 * Author: Alessandro Vernassa
 * 
 */

var GPS_location = {
    watchid: null,
    alertflag: false,
    speed: 0,
    latitude: null,
    longitude: null,
    accuracy: 0,
    log: "",
    isOn:false,
    start: function ()
    {
        this.getSpeed();
    },
    getSpeed: function ()
    {
        try {
            if (this.watchid !== null)
            {
                navigator.geolocation.clearWatch(this.watchid);
            }
        } catch (e) {
            this.log = e.toString();
            console.log(e);
        }
        try {
            var options = {
                enableHighAccuracy: true,
                timeout: 120000
            };
            //console.log("start get speed");
            this.watchid = navigator.geolocation.watchPosition(this.OnPositionDetected, this.error, options);
        } catch (e) {
            this.log = e.toString();
            console.log(e);
        }

    },
    convertToRadian: function (numericDegree) {
        return numericDegree * Math.PI / 180;
    },
    /**
     * 
     * @param {type} longitude1
     * @param {type} latitude1
     * @param {type} longitude2
     * @param {type} latitude2
     * @returns {Number}
     */
    calculateDistance: function (longitude1,latitude1, longitude2, latitude2) {
        // Calculate distance between mountain peak and current location
        // using the Haversine formula
        var earthRadius = 6373044.737; // Radius of the earth in km
        var dLatitude = this.convertToRadian(latitude2 - latitude1);
        var dLongitude = this.convertToRadian(longitude2 - longitude1);
        var a = Math.sin(dLatitude / 2) * Math.sin(dLatitude / 2) + Math.cos(this.convertToRadian(latitude1)) * Math.cos(this.convertToRadian(latitude2)) * Math.sin(dLongitude / 2) * Math.sin(dLongitude / 2);
        var greatCircleDistance = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var distance = earthRadius * greatCircleDistance; // distance converted to m from radians
        return Math.round(distance);
    },
    distanceFromMyPosition: function (latitude1, longitude1) {
        // Calculate distance between mountain peak and current location
        // using the Haversine formula
        var latitude2 = this.latitude;
        var longitude2 = this.longitude;
        var earthRadius = 6373044.737; // Radius of the earth in km
        var dLatitude = this.convertToRadian(latitude2 - latitude1);
        var dLongitude = this.convertToRadian(longitude2 - longitude1);
        var a = Math.sin(dLatitude / 2) * Math.sin(dLatitude / 2) + Math.cos(this.convertToRadian(latitude1)) * Math.cos(this.convertToRadian(latitude2)) * Math.sin(dLongitude / 2) * Math.sin(dLongitude / 2);
        var greatCircleDistance = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var distance = earthRadius * greatCircleDistance; // distance converted to m from radians
        return Math.round(distance);
    },
    /**
     * 
     * @param {type} position
     * @returns {undefined}
     */
    OnPositionDetected: function (position)
    {
        try {
            var speed = position.coords.speed; //The speed in meters per second
            var mhp = speed * 2.236936;
            var crd = position.coords;
            if (speed == null)
                speed = 0;
            GPS_location.speed = speed;
            GPS_location.latitude = crd.latitude;
            GPS_location.longitude = crd.longitude;
            GPS_location.accuracy = crd.accuracy;
            GPS_location.log = "";
            GPS_location.log += ('Latitude : ' + crd.latitude);
            GPS_location.log += ('<br />Longitude: ' + crd.longitude);
            GPS_location.log += ('<br />Accuracy: ' + crd.accuracy + ' m.');
            GPS_location.log += ('<br />Speed: ' + position.coords.speed + 'm/s');
            this.isOn=true;
        } catch (e) {
            this.log = e.toString();
            this.isOn=false;
            console.log(e);
        }
    },
    strLog: function ()
    {
        var ret = "";
        ret += this.latitude + " " + this.longitude;

        return ret;
    },
    /**
     * 
     * @param {type} err
     * @returns {undefined}
     */
    error: function (err) {
        this.isOn=false;
        if (!this.alertflag) {
            alert("If the GPS is OFF turn it ON before you start this application.\nOtherwise, the speed will not be displayed");
        }
        this.alertflag = true;
        try {
            console.log(err);
            this.log = err.toString();

        } catch (e)
        {
            console.log(e.toString());
            this.log = e.toString();
        }
    }

};


