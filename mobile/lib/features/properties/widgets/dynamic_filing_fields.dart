import 'package:flutter/material.dart';

class DynamicFilingFields extends StatelessWidget {
  final List<dynamic> fields;
  final Map<String, dynamic> values;
  final void Function(String key, dynamic value) onChange;

  const DynamicFilingFields({
    super.key,
    required this.fields,
    required this.values,
    required this.onChange,
  });

  @override
  Widget build(BuildContext context) {
    if (fields.isEmpty) return const SizedBox.shrink();

    final widgets = <Widget>[];
    for (final raw in fields) {
      final field = Map<String, dynamic>.from(raw as Map);
      final key = field['key'] as String? ?? '';
      final label = field['label'] as String? ?? key;
      final type = field['type'] as String? ?? 'text';
      final required = field['required'] == true;
      final val = values[key];

      if (type == 'boolean') {
        widgets.add(SwitchListTile(
          title: Text(label),
          value: val == true,
          onChanged: (v) => onChange(key, v),
        ));
        continue;
      }

      if (type == 'select' || type == 'province_select') {
        final options = (field['options'] as List?) ?? [];
        widgets.add(DropdownButtonFormField<String>(
          initialValue: val?.toString().isNotEmpty == true ? val.toString() : null,
          decoration: InputDecoration(labelText: '$label${required ? ' *' : ''}'),
          items: [
            const DropdownMenuItem(value: null, child: Text('انتخاب کنید')),
            for (final o in options)
              DropdownMenuItem(
                value: '${(o as Map)['value'] ?? o}',
                child: Text('${o['label'] ?? o['value'] ?? o}'),
              ),
          ],
          onChanged: (v) => onChange(key, v ?? ''),
        ));
        widgets.add(const SizedBox(height: 8));
        continue;
      }

      if (type == 'textarea') {
        widgets.add(TextFormField(
          initialValue: val?.toString() ?? '',
          decoration: InputDecoration(labelText: '$label${required ? ' *' : ''}'),
          maxLines: 3,
          onChanged: (v) => onChange(key, v),
        ));
        widgets.add(const SizedBox(height: 8));
        continue;
      }

      if (type == 'number' || type == 'jalali_date') {
        widgets.add(TextFormField(
          initialValue: val?.toString() ?? '',
          decoration: InputDecoration(labelText: '$label${required ? ' *' : ''}'),
          keyboardType: TextInputType.number,
          textDirection: TextDirection.ltr,
          onChanged: (v) => onChange(key, v),
        ));
        widgets.add(const SizedBox(height: 8));
        continue;
      }

      widgets.add(TextFormField(
        initialValue: val?.toString() ?? '',
        decoration: InputDecoration(labelText: '$label${required ? ' *' : ''}'),
        onChanged: (v) => onChange(key, v),
      ));
      widgets.add(const SizedBox(height: 8));
    }

    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: widgets);
  }
}
