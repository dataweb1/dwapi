class Validation {
    constructor(){}

    is_empty(value){
        if (value !== '' && value !== null && typeof value !== 'undefined') {
            return true
        }
        return false
    }
}


class Controller {
    constructor(){} 

    control_inputs(){
        var submittable= true

        var inputs=  $('input,textarea,select').filter('required')

        inputs.forEach(element => {
            const value = element.value
            console.log(value)
            
            if(new Validation().is_empty(value)){
                new Html().add_class(element)
                submittable =false
            }
        });

        if(submittable){
            new Ajax().run()
        }
    }
}



class Html {
    constructor(){}
    add_class(element){
        element.classList.add('was-validated')
    }
}

class Ajax{
    constructor(){}

    run(){

    }
}

document.getElementById('inloggen_knop').addEventListener('click',new Controller().control_inputs())