import 'package:flutter/material.dart';
import 'package:vmfs_app/config/theme.dart';

enum StepIndicatorState { pending, active, done }

class StepIndicator extends StatelessWidget {
  const StepIndicator({
    super.key,
    required this.totalSteps,
    required this.currentStep,
  });

  final int totalSteps;
  final int currentStep;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(totalSteps, (index) {
        final state = index < currentStep
            ? StepIndicatorState.done
            : index == currentStep
                ? StepIndicatorState.active
                : StepIndicatorState.pending;

        return Expanded(
          child: Container(
            height: 4,
            margin: EdgeInsets.only(right: index < totalSteps - 1 ? 8 : 0),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(999),
              color: switch (state) {
                StepIndicatorState.done => VmfsTheme.success,
                StepIndicatorState.active => VmfsTheme.accentSky,
                StepIndicatorState.pending => VmfsTheme.border,
              },
            ),
          ),
        );
      }),
    );
  }
}
