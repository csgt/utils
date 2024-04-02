<template>
    <div class="card">
        <div class="card-header" :class="color">
           <span>{{ module.description }}</span>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-bs-toggle="collapse" :data-bs-target="'#collapse_body' + module.name ">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body collapse" :id="'collapse_body' + module.name ">
            <div class="form-check" v-for="mp in module.modulepermissions" :key="mp.name">
                <input :id="mp.name" class="form-check-input" type="checkbox" v-model="mp.enabled">
                <label :for="mp.name" class="form-check-label">{{ mp.permission }}</label>
            </div>
        </div>
        <div class="card-footer" >
            <div class="mb-1">
            <a href="javascript:void(0);" @click="set(true)">Todos</a> | <a href="javascript:void(0);" @click="set(false)">Ninguno</a>
            </div>
        </div>
    </div>
    <br />
</template>

<script>
export default {
    props: {
        module: null,
    },
    methods: {
        set(bool) {
            this.module.modulepermissions.forEach((mp) => {
                mp.enabled = bool;
            });
        }
    },
    computed: {
        color() {
            let clase = 'card-header';

            if (!this.module) {
                return clase;
            }

            let all = this.module.modulepermissions.reduce((carry, mp) => {
                return carry + 1;
            }, 0);

            let selected = this.module.modulepermissions.reduce((carry, mp) => {
                return carry + (mp.enabled? 1 : 0);
            }, 0);


            if (all === selected) {
                return `bg-success`;
            } else if (selected === 0) {
                return `bg-danger`;
            }
            return `bg-warning`;
        }
    }
}
</script>
