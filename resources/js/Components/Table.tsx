"use client"

import * as React from "react"
import { cn } from "cn"
import { Download } from "lucide-react"

function TableExport({ tableRef, label = "Exportar tabla" }: { tableRef: React.RefObject<HTMLTableElement | null>; label?: string }) {
  const exportCsv = () => {
    const table = tableRef.current
    if (!table) return
    const tableRows = Array.from(table.querySelectorAll("tr"))
    const actionIndex = Array.from(tableRows[0]?.querySelectorAll("th") ?? []).findIndex((cell) => cell.textContent?.trim().toLowerCase() === "acciones")
    const rows = tableRows.map((row) => Array.from(row.querySelectorAll("th, td"))
      .filter((_, index) => index !== actionIndex)
      .map((cell) => `"${(cell.textContent ?? "").replace(/\s+/g, " ").trim().replace(/"/g, '""')}"`)
      .join(","))
    const blob = new Blob(["\uFEFF" + rows.join("\r\n")], { type: "text/csv;charset=utf-8" })
    const url = URL.createObjectURL(blob)
    const anchor = document.createElement("a")
    anchor.href = url
    anchor.download = `${document.title.toLowerCase().replace(/[^a-z0-9]+/g, "-") || "tabla"}.csv`
    anchor.click()
    URL.revokeObjectURL(url)
  }

  return <button type="button" onClick={exportCsv} aria-label={label} title={label} className="mb-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"><Download className="size-4" />Exportar esta página (CSV)</button>
}

function Table({ className, ...props }: React.ComponentProps<"table">) {
  const tableRef = React.useRef<HTMLTableElement>(null)
  return (
    <div className="min-w-0 max-w-full">
      <TableExport tableRef={tableRef} />
      <div tabIndex={0} role="region" aria-label="Tabla de datos, desplaza horizontalmente para ver más columnas" data-slot="table-container" className="relative max-w-full w-full overflow-x-auto rounded-2xl border border-slate-200/80 bg-white shadow-[0_12px_32px_rgba(15,23,42,0.05)] dark:border-slate-700/80 dark:bg-slate-900 dark:shadow-[0_12px_32px_rgba(0,0,0,0.16)]">
        <table ref={tableRef} data-slot="table" className={cn("w-full caption-bottom text-sm", className)} {...props} />
      </div>
    </div>
  )
}

function TableHeader({ className, ...props }: React.ComponentProps<"thead">) {
  return (
    <thead
      data-slot="table-header"
      className={cn("bg-gradient-to-r from-slate-50 via-blue-50/70 to-slate-50 text-slate-600 dark:from-slate-800 dark:via-blue-950/50 dark:to-slate-800 dark:text-slate-300 [&_tr]:border-b [&_tr]:border-slate-200/80 dark:[&_tr]:border-slate-700", className)}
      {...props}
    />
  )
}

function TableBody({ className, ...props }: React.ComponentProps<"tbody">) {
  return (
    <tbody
      data-slot="table-body"
      className={cn("[&_tr:last-child]:border-0", className)}
      {...props}
    />
  )
}

function TableFooter({ className, ...props }: React.ComponentProps<"tfoot">) {
  return (
    <tfoot
      data-slot="table-footer"
      className={cn(
        "border-t bg-muted/50 font-medium [&>tr]:last:border-b-0",
        className
      )}
      {...props}
    />
  )
}

function TableRow({ className, ...props }: React.ComponentProps<"tr">) {
  return (
    <tr
      data-slot="table-row"
      className={cn(
        "border-b border-slate-100 transition-colors hover:bg-blue-50/50 has-aria-expanded:bg-blue-50/50 data-[state=selected]:bg-blue-50 dark:border-slate-800 dark:hover:bg-slate-800/70 dark:has-aria-expanded:bg-slate-800/70 dark:data-[state=selected]:bg-slate-800",
        className
      )}
      {...props}
    />
  )
}

function TableHead({ className, ...props }: React.ComponentProps<"th">) {
  return (
    <th
      data-slot="table-head"
      className={cn(
        "h-12 px-4 text-left align-middle text-xs font-bold uppercase tracking-[0.1em] whitespace-nowrap text-slate-600 dark:text-slate-300 [&:has([role=checkbox])]:pr-0",
        className
      )}
      {...props}
    />
  )
}

function TableCell({ className, ...props }: React.ComponentProps<"td">) {
  return (
    <td
      data-slot="table-cell"
      className={cn(
        "px-4 py-3.5 align-middle whitespace-nowrap text-slate-700 dark:text-slate-200 [&:has([role=checkbox])]:pr-0",
        className
      )}
      {...props}
    />
  )
}

function TableCaption({
  className,
  ...props
}: React.ComponentProps<"caption">) {
  return (
    <caption
      data-slot="table-caption"
      className={cn("mt-4 text-sm text-muted-foreground", className)}
      {...props}
    />
  )
}

export {
  Table,
  TableHeader,
  TableBody,
  TableFooter,
  TableHead,
  TableRow,
  TableCell,
  TableCaption,
}
