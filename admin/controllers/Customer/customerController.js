app.controller("newCustController",function($scope,$http,dataService){
        
    var ncc = this;
    ncc.newcust = {};
    ncc.newcust.lastName = "";
    ncc.newcust.firstName = "";
    ncc.newcust.gender = "";
    ncc.newcust.cellNo = "";
    ncc.newcust.email = "";
    ncc.newcust.birthDate = "";
    ncc.newcust.wedAniv = "";
    ncc.newcust.disPic = "";

    ncc.initDatePicker = function(){
        var date_input1=$('.dpic'); //our date input has the name "date"
        var container1=$('#newcustForm').length>0 ? $('#newcustForm').parent() : "body";
        date_input1.datepicker({
            format: 'dd/mm/yyyy',
            container: container1,
            todayHighlight: true,
            autoclose: true,
        });  
       

    };

    ncc.init = function(){
       ncc.initDatePicker();
    };

    ncc.init();
});