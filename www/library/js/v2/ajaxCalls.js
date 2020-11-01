export default class Ajax {

    run(){  
        console.log(this)
        
        $.ajax(this)
        .done(function (response) {
        console.log(response)    
        })
        .fail(function(failResponse){
            console.log(failResponse)
        })
    }

}



export class AjaxPost extends Ajax{
    constructor(){
        super()
        this.method= 'POST',
        this.headers ={}
    }
    
}

export  class User extends AjaxPost{
    constructor(request,project,data){
        super()
        this.url=`https://dwapi.dev/v2/user/${request}?project=${project}`,
        this.data = data
    }
    
}

export class Item extends AjaxPost {
    constructor(endpoint,request,project,formData){
        super()
        this.url=`https://dwapi.dev/v2/${endpoint}/${request}?project=${project}`,
        this.data = formData
    }
}