<template>
    <div :class="['form-group', className]">
        <label :for="field">{{label}}</label>
        <input v-if="type == 'text'" :name="field" type="text" class="form-control" v-model="value">
    </div>
</template>
<script>
    export default {
        props : {
            field : "",
            type : {
                type: String,
                default : "text"
            },
            className : {
                type: String,
                default : "col-sm-12"
            },
            label : "",
            value : null,
        }
    }
</script>
