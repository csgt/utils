<template>
    <div :class="color">
        <div class="card-header">
            {{ module.description }}
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-bs-toggle="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="form-check" v-for="mp in module.modulepermissions" :key="mp.name">
                <input :id="mp.name" class="form-check-input" type="checkbox" v-model="mp.enabled">
                <label :for="mp.name" class="form-check-label">{{ mp.permission }}</label>
            </div>
        </div>
        <div class="card-footer">
            <a href="javascript:void(0);" @click="set(true)">Todos</a> | <a href="javascript:void(0);" @click="set(false)">Ninguno</a>
        </div>
    </div>
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
            let clase = 'card';

            if (!this.module) {
                return clase;
            }

            let all = this.module.modulepermissions.reduce((carry, mp) => {
                return carry + (!mp.module ? 1 : 0);
            }, 0);

            let selected = this.module.modulepermissions.reduce((carry, mp) => {
                return carry + (mp.enabled && !mp.module ? 1 : 0);
            }, 0);

            if (all === selected) {
                return `${clase} bg-success`;
            } else if (selected === 0) {
                return `${clase} bg-danger`;
            }
            return `${clase} bg-warning`;
        }
    }
}
</script>
