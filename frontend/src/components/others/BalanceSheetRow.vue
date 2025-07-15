<template>
    <tr>
        <td :style="{ paddingLeft: `${depth * 20}px` }" class="px-4 py-2 border border-gray-200">
            {{ account.name || 'No Account Name' }}
        </td>
        <td class="px-4 py-2 border border-gray-200 text-right">
            {{ formatBalance(account.balance) }}
        </td>
    </tr>

    <!-- Loop untuk nested children -->
    <template v-if="account.children && account.children.length > 0">
        <BalanceSheetRow 
            v-for="(child, childIndex) in account.children" 
            :key="childIndex"
            :account="child"
            :depth="depth + 1"
        />
    </template>
</template>

<script>
export default {
    name: 'BalanceSheetRow',
    props: {
        account: Object,
        depth: {
            type: Number,
            default: 1
        }
    },
    methods: {
        formatBalance(value) {
            if (value === null || value === undefined) return '-'

            const number = parseFloat(value)
            const absValue = Math.abs(number)
            const formatted = new Intl.NumberFormat('id-ID', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }).format(parseFloat(absValue))

            return number < 0 ? `(${formatted})` : formatted
        }
    }
}
</script>
