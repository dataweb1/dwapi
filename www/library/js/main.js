import Validation from './validator.js'
import Html from './output.js'
import {Login,Register} from './ajax.js'

class dwapi {
    constructor(){} 

    control_inputs(table,form,project){
        var submittable= true
        var properties=  $(`[dw_form*=${form}] input:required[dw_property]`)

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
                new Create().create(table,form,project,data)
            }
        }
    }
}

function submit_login(){
    new dwapi().control_inputs('user','login','f5gh8JhjAXBd')
}


function submit_register(){
    new dwapi().control_inputs('user','register','f5gh8JhjAXBd')
}
document.querySelector('#inloggen_knop').addEventListener('click', submit_login)

document.querySelector('#register_button').addEventListener('click', submit_register)
