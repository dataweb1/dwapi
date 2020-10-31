export default class AjaxPost{
    constructor(){
        this.method= 'POST',
        this.headers ={}
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

export  class Login extends AjaxPost{
    constructor(project,data){
        super()
        this.url=`https://dwapi.dev/v2/user/login?project=${project}`,
        this.data = data
    }
}

export class Register extends AjaxPost{
    constructor(project,formData){
        super()
        this.url=`https://dwapi.dev/v2/user/register?project=${project}`,
        this.data = formData
    }
}

export class Create extends AjaxPost {
    constructor(table,project,formData){
        super()
        this.url=`https://dwapi.dev/v2/${table}/create?project=${project}`,
        this.data = formData
    }
}