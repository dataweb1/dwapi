import Validation from './validator.js'
import Html from './output.js'
import {Login,Register} from './ajax.js'

class dwapi {
    constructor(){} 

    control_submit(form,table,project){
        var submittable= true
        var properties=  $(`[dw_form*=${form}] input:required[dw_property], [dw_form*=${form}] textarea:required[dw_property]`)

        for (const element of properties) {
            
            const value = element.value

            if(new Validation().is_empty(value)){
                new Html().add_class(element)
                submittable =false
            }
        }

        if(submittable){

            var data ={}
            for (let i = 0; i < properties.length; i++) {
                const element = properties[i];
                const keys = element.getAttribute('dw_property')
                const values = element.value

                data[`${keys}`] = values
            }

            if(form=='login')
            {
                new Login(project,data).run()
            }
            else if (form=='register')
            {
                let values = data
                let formData ={values}
                new Register(project,formData).run()
            }
            else
            {
                let values = data
                let formData ={values}
                new Create().create(table,form,project,data)
            }
        }
    }

    control_email(){

    }
}



var submits = document.querySelectorAll('[dw_submit]')

if(submits.length==0){
    console.log('you must add dw_submit attribute to your submit button')
}else{
    submits.forEach(button => {
        let parameters = button.getAttribute('dw_submit').split(' ')
        let form =parameters[0]
        let table =parameters[1]
        let project = parameters[2]

        button.addEventListener('click',function(){
            new dwapi().control_submit(form,table,project)
        })
    });
}

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

    
}