class Validation {
    constructor(){}

    is_empty(value){
        if (value == '' || value == null || typeof value == 'undefined') {
            return true
        }
        return false
    }
}


class Controller {
    constructor(){} 

    control_inputs(){
        var submittable= true

        var inputs=  $('input:required')

        for (const element of inputs) {
            const value = element.value
            if(new Validation().is_empty(value)){
                new Html().add_class(element)
                submittable =false
            }
        }
    
        if(submittable){
            new Ajax().run()
        }
    }
}



class Html {
    constructor(){}
    add_class(element){
        element.classList.add('invalid')
    }
}

class Ajax{
    constructor(){}

    run(){
        console.log('works')
    }
}


function x(){
    new Controller().control_inputs()
}