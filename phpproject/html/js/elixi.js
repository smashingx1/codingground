var elixiApp = {

    devicePlatform : null,

    onDeviceReady: function() {
        elixiApp.devicePlatform = device.platform;
    },

    registration: {
        /**
         * Validates the initial registration form.
         *
         * @param form HTMLElement The form element.
         * @param submit boolean Whether this is a submission attempt.
         */
        validateForm: function(form, submit) {
            var self = this;
            if(self.alertTimeout) {
                clearTimeout(self.alertTimeout);
            }
            form = submit ? $(form).closest('form') : form = $(form);
            var dob = form.find('#dob');
            if(dob.val()) {
                var d = new Date(dob.val());
                var t = new Date();
                if(t.getFullYear() == d.getFullYear() && t.getMonth() == d.getMonth() && t.getDate() == d.getDate()) {
                    return;
                }
                var age = t.getFullYear() - d.getFullYear();
                var m = t.getMonth() - d.getMonth();
                if (m < 0 || (m === 0 && t.getDate() < d.getDate())) {
                    age--;
                }
                if(age < 21 && ($(document.activeElement).attr('id') != 'dob')) {

                    self.alertTimeout = setTimeout(function(){motocol_native.device.notify.alert("You must be 21 years old...");}, 1000);
                    return;
                }
            }
            var pass = form.find('#password');
            var conf = form.find('#passconf');
            if(pass.val() && conf.val() && pass.val() != conf.val()) {
                motocol_native.device.notify.alert("Passwords do not match!");
                return;
            }
            if(submit) {
                var elem = form.find('#tos');
                if(!elem.attr('checked')) {
                    motocol_native.device.notify.alert('Please accept the Terms of Service.');
                    return;
                }
                var req = ['dob', 'password', 'firstname', 'lastname', 'email', 'username'];
                for(var i=0;i<req.length;i++) {
                    elem = form.find('#' + req[i]);
                    if(!elem.val()) {
                        elem.focus();
                        motocol_native.device.notify.alert('Please fill out all fields.');
                        return;
                    }
                }
                form.submit();
                showLoading("Registering...");
            }
        },

        onSuccessRegistration: function (){
            hideLoading();
            $("#cc-link-continue-button").removeClass('ui-disabled');
        },

        onFailRegistration: function () {
            hideLoading();
            motocol_native.device.notify.alert('There was problem with the registration, please check your connection and try again');
            $A.archetypeChangePage('#login', {});
        }
    },

    linkCard: {
        linkCardSuccess: function() {
            if (MOTO.getData("Service", "initialcardlink") != null) {
                var linkCardURL = MOTO.getData("Service", "initialcardlink").linkurl[0];
                //setting location=no adds a close button to the inappbrowser on Android
                window.open(linkCardURL, '_blank', 'location=no');
            }
            else {
                motocol_native.device.notify.alert("Unknown error, please try again");
            }
        },

        linkCardFail: function() {
            /* Card Link Failure handled by @rchetype */
        }
    },

    redeemCredit: {
        redeemCreditSuccess: function() {
            if (MOTO.getData("Service", "redeemcredit") != null) {
                var responseStatus = MOTO.getData("Service", "redeemcredit").status[0];
                if (responseStatus == "Success") {
                    motocol_native.device.notify.alert("You've successfully redeem your ride");
                }
                else {
                    motocol_native.device.notify.alert("Something went wrong, please try again");
                }
            }
            else {
                motocol_native.device.notify.alert("Something went wrong, please try again");
            }
            $A.archetypeSectionRefreshData($("#user_rides_to_redeem_list"));
            $A.archetypeChangePage('#redeem-1',[]);
        },

        redeemCreditFail: function() {
            motocol_native.device.notify.alert("Failed to redeem credit, please try again");
            $A.archetypeSectionRefreshData($("#user_rides_to_redeem_list"));
            $A.archetypeChangePage('#redeem-1',[]);
        }
    },

    referAFriend: {
        referAFriendSuccess: function() {
            $("#refer-friend").popup("close");
            $A.hideLoading();
            if (MOTO.getData("Service", "refer_a_friend") && MOTO.getData("Service", "refer_a_friend").status.length == 1
                && MOTO.getData("Service", "refer_a_friend").status[0] == "Success") {
                motocol_native.device.notify.alert("You've successfully refer a friend!");
            }
            else {
                motocol_native.device.notify.alert("Unknown error, please try again later");
            }

            MOTO.truncateTable("Service", "refer_a_friend");
        },

        referAFriendFail: function() {
            $("#refer-friend").popup("close");
            $A.hideLoading();
            motocol_native.device.notify.alert("There was an error, please check your internet connection!");
            MOTO.truncateTable("Service", "refer_a_friend");
        }
    },
    sortDayPoints: function() {
        var days = $.mobile.activePage.find('[data-point-day]');
        var parent = $(days[0]).parent().parent();
        for(var i=0;i<7;i++) {
            var day = i ? i : 7;
            days.each(function(){
                if($(this).data('point-day') == day) {
                    parent.append($(this).parent());
                }
            });
        }
    }
};

//---------------------------------------------------------------
//----------------------jQuery events----------------------------
//---------------------------------------------------------------

$(document).on('click', '#logout-button', function (){
  $UTIL.reset();
});

$(document).on('click', '#refer-a-friend-submit-button', function (e){
    e.preventDefault();
    var theEmail = $(this).parent().find("#friend").val();
    var email_check = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i;

    if(theEmail == "" || typeof theEmail == "undefined"){
        motocol_native.device.notify.alert("Please enter an email address");
    }
    else if (!email_check.test(theEmail)){
        motocol_native.device.notify.alert(theEmail + " is not a valid email address");
    }
    else {
        $(this).submit();
    }
});

$(document).on('click', '.coming-soon-button', function (){
    motocol_native.device.notify.alert("Please login");
});

$(document).on('click', '#cc-link-continue-button', function () {
    if (!($(this).hasClass('ui-disabled'))) {
        $OAUTH._loginProcess($("#username").val(), $("#password").val());
    }
});

$(document).on('pageshow', "div[id^='ride-detail-']", function (){
    if (elixiApp.devicePlatform == "Android") {
        var androidURL = $("#downloadAppButton").attr("data-url-android");
        if (typeof  androidURL !== 'undefined' && androidURL != null && androidURL != '') {
            $("#downloadAppButton").show();
        }
    }
    else if (elixiApp.devicePlatform == "iOS") {
        var iOSURL = $("#downloadAppButton").attr("data-url-android");
        if (typeof  iOSURL !== 'undefined' && iOSURL != null && iOSURL != '') {
            $("#downloadAppButton").show();
        }
    }
});

$(document).on('pageshow', "#home, #account-information", function (){
    var showRides = function() {
        try {
            //map reduce to display the number of rides redeemed
            var fm = MOTO.FilterMap.init("rchetype_elixi", "user_rides");
            fm.mapReduce("is_redeemed", "1", "EQ");
            $(".display-number-of-rides").html(fm.idxCount());
        }
        catch(e) {
            setTimeout(showRides, 100);
        }
    };
    setTimeout(showRides, 100);
});

$(document).on('pageshow', "#login", function (){
    $('#home').remove();
});

$(document).on('pageshow', "#find-business", function (){
    var reorderMap = function() {
        try {
            $('#mapFilter').find('.ui-btn-corner-all').removeClass('ui-btn-corner-all');
        }
        catch(e) {
            setTimeout(reorderMap, 100);
        }
    };
    setTimeout(reorderMap, 0);
});

$(document).ready(function (){
    document.addEventListener("deviceready", elixiApp.onDeviceReady, false);
    var centerMe = function() {
        var elems = $.mobile.activePage.find('.centerMe');
        if(elems.length) {
            elems.each(function(){
                var me = $(this);
                var parent = me.parent();
                var px = (parent.height() - me.outerHeight()) / 2;
                me.css('margin', px + 'px');
                console.log(px);
            });
        }
    };
    setInterval(centerMe, 1000);
});

$(document).on('click', ".ride-item-not-redeemed", function(e) {
   var rideValue = null;
   var rideBalance = null;
    try {
        rideValue = $(this).find(".ride-dl-amount").text().replace("$", "");
        rideBalance = $A.getUserScopeObj().ride_balance;
   } catch(error) {
        motocol_native.device.notify.alert("There was a problem getting the ride balance!");
   }
   if (rideBalance != null && rideValue != null &&
       !isNaN(rideBalance) && !isNaN(rideValue)) {
        if (rideBalance < 5) {
            motocol_native.device.notify.alert("Partial redemption has a $5.00 minimum");
        }
        else if (rideBalance < rideValue) {
            motocol_native.device.notify.alert("You don't have enough credit to redeem this ride");
        }
        else {
            var response = confirm("Please be aware that if you do a partial redemption, " +
                "you will not be able to redeem the rest. Are you sure you want to redeem this ride?");
            if (response == true) {
                //everything checks out, we can return from function
                return;
            }
            else {
                //user cancelled confirmation dialog
            }
        }
   }
   else {
       motocol_native.device.notify.alert("There was a problem getting the ride balance and/or current ride points. Please logout and try again.");
   }
   e.preventDefault();
   e.stopImmediatePropagation();
   //e.stopPropagation(); //needed?
});

$(document).on('click', ".open-in-app-browser", function (e) {
    var theURL = $(this).attr("data-url");
    if (theURL.substring(0,4) != 'http') {
        theURL = 'http://' + theURL;
    }
    window.open(theURL, '_blank', 'location=yes');
});

$(document).on('click', ".open-in-system-browser", function (e) {
    var URLtoOpen = null;
    var androidURL = $(this).attr("data-url-android");
    var iOSURL = $(this).attr("data-url-ios");
    var theURL = $(this).attr("data-url");

    if (elixiApp.devicePlatform == "Android") {
        URLtoOpen = (typeof androidURL !== 'undefined') ? androidURL : theURL;
    }
    else if (elixiApp.devicePlatform == "iOS") {
        URLtoOpen = (typeof iOSURL !== 'undefined') ? iOSURL : theURL;
    }
    else {
        URLtoOpen = theURL;
    }
    if (typeof URLtoOpen !== 'undefined' && URLtoOpen !== null) {
        if (URLtoOpen.substring(0,4) != 'http') {
            URLtoOpen = 'http://' + URLtoOpen;
        }
        window.open(URLtoOpen, '_system', 'location=yes');
    }
    else {
        console.log("==Error==: URL not defined - elixi.js line 137");
    }
});

/*
== IMPORTANT! ==
The line below fixes bug causing the e.preventDefault() to not work
due to the fact that it prevents the default behavior for the archetype
anchor click event instead of the '.ride-item-not-redeemed' event. This
is caused by the e.preventDefault() being bind to the archetype event.
 */
$(document).data('events').click.reverse();

//---------------------------------------------------------------
//------------------END jQuery events----------------------------
//---------------------------------------------------------------
