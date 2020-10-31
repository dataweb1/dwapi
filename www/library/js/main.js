import Validation from './validator.js'
import Html from './output.js'
import LoginAjax from './ajax.js'

class dwapi {
    constructor(){} 

    control_inputs(project,){
        var submittable= true
        var inputs=  $('input:required')//custom attributes

        for (const element of inputs) {
            
            const value = element.value

            if(new Validation().is_empty(value)){
                new Html().add_class(element)
                submittable =false
            }
        }

        if(submittable){
            const email= $('input:required[type="email"]')[0].value;
            const password = $('input:required[type="password"]')[0].value;
            new LoginAjax(project,email,password).run()
        }
    }
}

function submit(){
    new dwapi().control_inputs('f5gh8JhjAXBd')
}
document.querySelector('#inloggen_knop').addEventListener('click', submit)

