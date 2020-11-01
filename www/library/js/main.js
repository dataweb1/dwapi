import Validation from './validator.js'
import Html from './output.js'
import {Login,Register} from './ajax.js'

class dwapi {
    constructor(){} 

    control_inputs(form,table,project){
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
}



var submits = document.querySelectorAll('[dw_submit]')

if(submits.length==0){
    console.log('you must add dw_submit attribute to your submit button')
}else{
    submits.forEach(button => {
        var parameters = button.getAttribute('dw_submit').split(' ')

        button.addEventListener('click',function(){
            submit(parameters[0],parameters[1],parameters[2])
        })
    });
}

function submit(form,table,project){
    new dwapi().control_inputs(form,table,project) 
}

