import Validation from './validator.js'
import Html from './output.js'
import LoginAjax from './ajax.js'

class dwapi {
    constructor(){} 

    control_inputs(project,){
        var submittable= true
        var properties=  $('input:required[dw_property]')

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
           // var formData = new FormData()
            //formData.set('email',data)
            new LoginAjax(project,data).run()
        }
    }
}

function submit(){
    new dwapi().control_inputs('f5gh8JhjAXBd')
}
document.querySelector('#inloggen_knop').addEventListener('click', submit)

