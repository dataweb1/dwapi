export default class Validation {
    constructor(){}

    is_empty(value){
        if (value == '' || value == null || typeof value == 'undefined') {
            return true
        }
        return false
    }
}