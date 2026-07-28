import { Injectable } from '@nestjs/common';

@Injectable()
export class AppService {
  getHello(): { message: string; timestamp: string } {
    return {
      message: '🚀 AAMEVI API - E-Learning Platform',
      timestamp: new Date().toISOString(),
    };
  }
}
