export default class Validation {
    constructor(){}

    isEmpty(value){
        if (value == '' || value == null || typeof value == 'undefined') {
            return true
        }
        return false
    }
}