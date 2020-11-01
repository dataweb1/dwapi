import Validation from './validator.js'
import Html from './output.js'
import {User,Item} from './ajaxCalls.js'

class dwapi {

    control_submit(request,endpoint,projectKey){
        var submittable= true
        var properties=  $(`[dwForm*=${request}] input:required[dwProperty], [dwForm*=${request}] textarea:required[dwProperty]`)

        for (const element of properties) {
            
            const value = element.value

            if(new Validation().isEmpty(value)){
                new Html().addClass(element)
                submittable =false
            }
        }

        if(submittable){

            var data ={}
            for (let i = 0; i < properties.length; i++) {
                const element = properties[i];
                const keys = element.getAttribute('dwProperty')
                const values = element.value

                data[`${keys}`] = values
            }

            if(request=='login'){
                new User(request,projectKey,data).run()
            } 
            else if (request=='register'){
                let values = data
                let formData ={values}
                new User(request,projectKey,formData).run()
            }
            else{
                let values = data
                let formData ={values}
                new Item(request,endpoint,projectKey,formData)
            }
        }
    }

    loadApp(){
                
        var submit = document.querySelectorAll('[dwRequest]')
        var project = document.querySelectorAll('[dwProject]')
        var projectKey;

        if(project.length==0){
            console.log('your document should have a dwProject attribute')
        }else{
            projectKey = project[0].getAttribute('dwProject')
        }

        if(submit.length==0){
            console.log('your submit buttons should have dwRequest attributes')
        }else{

            submit.forEach(button => {

                var endpoint= button.getAttribute('dwEndpoint')
                var request = button.getAttribute('dwRequest')

                button.addEventListener('click',function(){
                    new dwapi().control_submit(request,endpoint,projectKey)
                }) 
            });
        }
    }
}

new dwapi().loadApp()

/* 
var email_requirements = document.querySelectorAll('[dw_email]')
var password_requirements = document.querySelectorAll('[dw_password]')

if (password_requirements.length!==0){
    let minChar=''
    let maxChar = ''
    let uppercase = ''
    let lowercase = ''
    let specialChar = ''

    password_requirements.forEach(element => {
        let params = element.getAttribute('dw_password')

        if(params.match(/min-+\d+/g)!==null){
            minChar = params.match(/min-+\d+/g)[0].split('-')[1]
        }

        if(params.match(/max-+\d+/g)!==null){
            maxChar = params.match(/max-+\d+/g)[0].split('-')[1]
        }

        if(params.match(/up-+\d+/g)!==null){
            uppercase = params.match(/up-+\d+/g)[0].split('-')[1]
        }

        if(params.match(/lo-+\d+/g)!==null){
            lowercase = params.match(/lo-+\d+/g)[0].split('-')[1]
        }   
        
        if(params.match(/sp-+\d+/g)!==null){
            specialChar = params.match(/sp-+\d+/g)[0].split('-')[1]
        }   

    });

    
} */