export default class LoginAjax{ /// use inheritance for ajax calls
    constructor(project,data){
     
        this.url = `https://dwapi.dev/v2/user/login?project=${project}`
        this.method= 'POST',
        this.headers ={},
        this.data = data  
    }
    
    run(){
        console.log(this)
        
        $.ajax(this)
        .done(function (response) {
        console.log(response)    
        })
        .fail(function(fail_response){
            console.log(fail_response)
        })
    }
}
